package main

import (
	"log"
	"net/http"
	"os"
	"path/filepath"

	"github.com/pgl/museum-of-dns/internal/museum"
)

func main() {
	root := os.Getenv("MUSEUM_CONTENT_DIR")
	if root == "" {
		root = "content/exhibits"
	}
	address := os.Getenv("MUSEUM_ADDR")
	if address == "" {
		address = "127.0.0.1:8080"
	}

	app, err := museum.New(museum.Config{
		ContentDir: root,
		StaticDir:  "static",
		Password:   os.Getenv("MUSEUM_ADMIN_PASSWORD"),
		Secret:     os.Getenv("MUSEUM_SESSION_SECRET"),
		Secure:     os.Getenv("MUSEUM_SECURE_COOKIES") == "true",
	})
	if err != nil {
		log.Fatal(err)
	}

	log.Printf("Museum of DNS listening on http://%s (content: %s)", address, filepath.Clean(root))
	log.Fatal(http.ListenAndServe(address, app.Handler()))
}
