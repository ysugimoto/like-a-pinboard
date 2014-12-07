package handler

import (
	"database/sql"
	"fmt"
	"github.com/ysugimoto/husky"
)

func Add(d *husky.Dispatcher) {
	db := husky.NewDb("root:root@tcp(localhost:3306)/pinboard")

	req := d.Input.GetRequest()
	req.ParseForm()
	db.TransBegin()

	url := req.FormValue("url")
	title := req.FormValue("title")
	// "tag" field should treat as array
	tag := req.Form["tag"]

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
		db.TransRollback()
		message := "Token not matched!"
		SendError(d, message)
		return
	}

	// Insert URL
	insert := map[string]interface{}{
		"url":     url,
		"title":   title,
		"user_id": userId,
	}
	result, insertErr := db.Insert("pb_urls", insert)
	if insertErr != nil {
		db.TransRollback()
		message := fmt.Sprintf("Query Error: %v\n", insertErr)
		SendError(d, message)
		return
	}

	urlId, _ := result.LastInsertId()
	// Insert tags
	for _, t := range tag {
		ins := map[string]interface{}{
			"url_id":  urlId,
			"name":    t,
			"user_id": userId,
		}
		if _, err := db.Insert("pb_tags", ins); err != nil {
			db.TransRollback()
			message := fmt.Sprintf("Query Error: %v\n", err)
			SendError(d, message)
			return
		}
	}

	db.TransCommit()
	SendOK(d, "URL pin have saved!")
}
