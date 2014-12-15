package handler

import (
	"database/sql"
	"fmt"
	"github.com/ysugimoto/husky"
)

func Accept(d *husky.Dispatcher) {
	db := husky.NewDb("root:root@tcp(localhost:3306)/pinboard")
	req := d.Input.GetRequest()
	token := req.Header.Get("X-LAP-Token")
	if token == "" {
		SendError(d, "Accept Error")
		return
	}

	// match token
	var userName string
	row := db.Select("name").Where("token", "=", token).GetRow("pb_users")
	if err := row.Scan(&userName); err != nil || err == sql.ErrNoRows {
		fmt.Printf("%v\n", err)
		message := "Token not matched"
		SendError(d, message)
		return
	}

	SendOK(d, userName)
}
