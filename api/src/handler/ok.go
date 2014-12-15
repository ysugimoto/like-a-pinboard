package handler

import (
	"encoding/json"
	"github.com/ysugimoto/husky"
)

func SendOK(d *husky.Dispatcher, message string) {
	d.Output.SetHeader("Content-Type", "application/json")
	d.Output.SetHeader("Access-Control-Allow-Origin", "*")
	d.Output.SetHeader("Access-Control-Allow-Headers", "X-LAP-Token")
	d.Output.SetStatus(200)

	out, _ := json.Marshal(map[string]string{
		"message": message,
	})

	d.Output.Send(out)
}
