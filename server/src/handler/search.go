package handler

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"github.com/ysugimoto/husky"
	"strconv"
	"strings"
)

type SearchResult struct {
	Url   string `json:"url"`
	Title string `json:"title"`
	Tag   string `json:"tag"`
}

func Search(d *husky.Dispatcher) {
	db := husky.NewDb("root:root@tcp(localhost:3306)/pinboard")
	req := d.Input.GetRequest()

	// check token
	token := req.Header.Get("X-LAP-Token")
	if token == "" {
		db.TransRollback()
		message := "Token not found"
		SendError(d, message)
		return
	}

	// Check token and get userId
	var userId int
	row := db.Select("id").Where("token", "=", token).GetRow("pb_users")
	if err := row.Scan(&userId); err != nil || err == sql.ErrNoRows {
		message := "Token not matched!"
		SendError(d, message)
		return
	}

	var qs []string
	var limit int

	param := req.URL.Query()
	if get, ok := param["q"]; !ok {
		qs = append(qs, "")
	} else {
		qs = get
	}
	if get, ok := param["l"]; !ok {
		limit = 10
	} else {
		if parse, err := strconv.Atoi(get[0]); err != nil {
			limit = parse
		} else {
			limit = 10
		}
	}
	q := strings.Split(qs[0], " ")

	// Search Query
	query := "SELECT U.url, U.title, T.name FROM pb_tags as T "
	query += "JOIN pb_urls as U ON ( T.url_id = U.id ) "
	query += "WHERE T.user_id = ? AND "
	bind := []interface{}{userId}
	where := []string{}

	for _, l := range q {
		where = append(where, "T.name LIKE ?")
		bind = append(bind, "%"+l+"%")
	}
	query += strings.Join(where, " OR ")
	query += " LIMIT " + fmt.Sprint(limit)

	rows, err := db.Query(query, bind...)
	if err != nil {
		message := fmt.Sprintf("Query Error: %v", err)
		SendError(d, message)
		return
	}

	var result []SearchResult
	for rows.Next() {
		fmt.Println("found")
		r := SearchResult{}
		rows.Scan(&r.Url, &r.Title, &r.Tag)
		result = append(result, r)
	}

	if encode, err := json.Marshal(result); err != nil {
		SendError(d, fmt.Sprintf("Endode error: %v", err))
		return
	} else {
		d.Output.SetHeader("Content-Type", "application/json")
		d.Output.SetHeader("Access-Control-Allow-Origin", "*")
		d.Output.SetHeader("Access-Control-Allow-Headers", "X-LAP-Token")
		d.Output.SetStatus(200)
		d.Output.Send(encode)
	}
}
