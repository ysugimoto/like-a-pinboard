package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
	"github.com/ysugimoto/husky"
)

//const DSN = "username:password@tcp(localhost:3306)/pinboard"
const DSN = "root:root@tcp(localhost:3306)/pinboard"

var db *sql.DB
var dbError error

func main() {
	db, dbError = sql.Open("mysql", DSN)
	if dbError != nil {
		fmt.Printf("Database Connection Error: %v\n", dbError)
		return
	}

	defer db.Close()

	app := husky.NewApp()
	app.AcceptCORS()
	app.Post("/add", handleAdd)
	app.Get("/accept", handleAccept)

	app.Serve()
}

func handleAccept(d *husky.Dispatcher) {
	req := d.Input.GetRequest()
	q := req.URL.Query()
	token, ok := q["token"]
	if !ok {
		sendError(d, "Accept Error")
		return
	}

	fmt.Println(token[0])

	// match token
	var userName string
	query := "SELECT name FROM pb_users WHERE token = ? LIMIT 1"
	if err := db.QueryRow(query, token[0]).Scan(&userName); err != nil || err == sql.ErrNoRows {
		fmt.Printf("%v\n", err)
		message := "Token not matched"
		sendError(d, message)
		return
	}

	sendOK(d, userName)
}

func handleAdd(d *husky.Dispatcher) {
	req := d.Input.GetRequest()
	req.ParseForm()
	trans, _ := db.Begin()

	url := req.FormValue("url")
	title := req.FormValue("title")
	// "tag" field should treat as array
	tag := req.Form["tag"]

	// check token
	getParam := req.URL.Query()
	token, ok := getParam["token"]
	if !ok {
		trans.Rollback()
		message := "Token not matched"
		sendError(d, message)
		return
	}

	// Check token and get userId
	var userId int
	query := "SELECT id FROM pb_users WHERE token = ? LIMIT 1"
	if err := trans.QueryRow(query, token[0]).Scan(&userId); err != nil || err == sql.ErrNoRows {
		trans.Rollback()
		message := "Token not matched!"
		sendError(d, message)
		return
	}

	// Insert URL
	query = "INSERT IGNORE INTO pb_urls (url, title, user_id) VALUES (?, ?, ?);"
	result, insertErr := trans.Exec(query, url, title, userId)
	if insertErr != nil {
		trans.Rollback()
		message := fmt.Sprintf("Query Error: %v\n", insertErr)
		sendError(d, message)
		return
	}

	urlId, _ := result.LastInsertId()
	// Insert tags
	query = "INSERT IGNORE INTO pb_tags (url_id, name, user_id) VALUES (?, ?, ?);"
	for _, t := range tag {
		if _, err := trans.Exec(query, urlId, t, userId); err != nil {
			trans.Rollback()
			message := fmt.Sprintf("Query Error: %v\n", err)
			sendError(d, message)
			return
		}
	}

	trans.Commit()
	sendOK(d, "URL pin have saved!")
}

func sendError(d *husky.Dispatcher, message string) {
	d.Output.SetHeader("Content-Type", "application/json")
	d.Output.SetHeader("Access-Control-Allow-Origin", "*")
	d.Output.SetStatus(400)

	out, _ := json.Marshal(map[string]string{
		"message": message,
	})

	d.Output.Send(out)
}

func sendOK(d *husky.Dispatcher, message string) {
	d.Output.SetHeader("Content-Type", "application/json")
	d.Output.SetHeader("Access-Control-Allow-Origin", "*")
	d.Output.SetStatus(200)

	out, _ := json.Marshal(map[string]string{
		"message": message,
	})

	d.Output.Send(out)
}
