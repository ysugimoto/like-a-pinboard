#!/bin/sh

export GOPATH=`pwd`
echo "Setting GOPATH to ${GOPATH}."

echo "Getting dependency packages..."
go get -u github.com/ysugimoto/husky
go get -u github.com/go-sql-driver/mysql
