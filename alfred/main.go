package main

import (
	"encoding/json"
	"encoding/xml"
	"fmt"
	"io/ioutil"
	"net/http"
	"net/url"
	"os"
	"strings"
)

// @see https://github.com/handlename/alfred-metacpan-workflow/blob/master/workflow.go

// Alfred Structure XML
type AlfredXML struct {
	XMLName xml.Name        `xml:"items"`
	Item    []AlfredXMLItem `xml:"item"`
}

// Alfred item XML
type AlfredXMLItem struct {
	XMLName  xml.Name `xml:"item"`
	Arg      string   `xml:"arg,attr"`
	Title    string   `xml:"title"`
	Subtitle string   `xml:"subtitle"`
}

type ServerResponseItem struct {
	Title string `json:"title"`
	Url   string `json:"url"`
	Tag   string `json:"tag"`
}

func main() {
	args := os.Args[1:]
	config, ok, msg := loadConfig()
	if !ok {
		fmt.Println(errorXML(msg))
		return
	}

	requestURL, _ := config["url"]
	requestToken, _ := config["token"]

	val := url.Values{}
	val.Add("q", strings.Join(args, " "))

	if resp, err, msg := sendSearchRequest(requestURL, requestToken, val); err != nil {
		fmt.Println(errorXML(msg))
	} else {
		defer resp.Body.Close()
		if buffer, err := ioutil.ReadAll(resp.Body); err != nil {
			fmt.Println(errorXML("Response could not read"))
		} else {
			var response []ServerResponseItem
			if err := json.Unmarshal(buffer, &response); err != nil {
				fmt.Println(errorXML("API Returned Invalid format response"))
			} else {
				fmt.Println(resultXML(response))
			}
		}
	}
}

func sendSearchRequest(requestURL, requestToken string, query url.Values) (resp *http.Response, err error, msg string) {
	if request, err := http.NewRequest("GET", requestURL+"search?"+query.Encode(), nil); err != nil {
		return nil, err, "Unexpected Error NewRequest()"
	} else {
		request.Header.Set("X-LAP-Token", requestToken)
		client := &http.Client{}
		if resp, err := client.Do(request); err != nil {
			return nil, err, "Request Error. Check server setting"
		} else {
			return resp, nil, ""
		}
	}
}

func loadConfig() (config map[string]string, ok bool, msg string) {
	configPath := os.Getenv("HOME") + "/.laprc"
	if _, err := os.Stat(configPath); err != nil {
		return nil, false, "$HOME/.laprc file not found"
	}

	if buffer, err := ioutil.ReadFile(configPath); err != nil {
		return nil, false, "$HOME/.laprc read error"
	} else {
		if err := json.Unmarshal(buffer, &config); err != nil {
			return nil, false, "$HOME/.laprc is invalid json format"
		}

		return config, true, ""
	}
}

func resultXML(result []ServerResponseItem) string {
	response := AlfredXML{}

	for _, item := range result {
		response.Item = append(response.Item, AlfredXMLItem{
			Arg:      item.Url,
			Title:    item.Title,
			Subtitle: item.Url,
		})
	}

	if ret, err := xml.Marshal(response); err != nil {
		return errorXML("XML Build Error")
	} else {
		return string(ret)
	}
}

func errorXML(msg string) string {
	return xml.Header + `
<items>
  <item arg="">
    <title>ERROR</title>
    <subtitle>` + msg + `</subtitle>
  </item>
</items>`
}
