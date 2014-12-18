BIN=lap
PRODUCT=lap-search-tool.alfredworkflow

build/$(PRODUCT): $(BIN)
	zip -jv $@ $(BIN) icon.png info.plist 1BAF8A31-D6FF-4D50-9475-6799E4C1F0C7.png

$(BIN): *.go
	go build -o $(BIN) main.go

.PHONY: clean
clean:
	rm $(BIN) build/$(PRODUCT)
