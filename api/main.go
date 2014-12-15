package main

import (
	"github.com/ysugimoto/husky"
	"handler"
)

//const DSN = "username:password@tcp(localhost:3306)/pinboard"
const DSN = "root:root@tcp(localhost:3306)/pinboard"

func main() {
	app := husky.NewApp()
	app.AcceptCORS([]string{"X-Requested-With", "X-LAP-Token"})
	app.Post("/add", handler.Add)
	app.Get("/accept", handler.Accept)
	app.Get("/search", handler.Search)

	app.Serve()
}
