#!/bin/sh

export GOPATH=`pwd`
echo "Setting GOPATH to ${GOPATH}."

echo "Getting dependency packages..."
go get github.com/ysugimoto/husky
go get github.com/go-sql-driver/mysql
