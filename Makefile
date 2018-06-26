
.PHONY: build
build:
	composer install

.PHONY: test
test: build
	vendor/bin/phpunit tests

.PHONY: clean
clean:
	rm -rf vendor $(VENV_DIR)
