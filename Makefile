
.PHONY: build
build:
	composer install

.PHONY: test
test: build
	vendor/bin/phpunit tests

.PHONY: test-docker
test-docker:
	docker run --rm -ti -w="/app" -v="$PWD:/app" composer:lts make test

.PHONY: clean
clean:
	rm -rf vendor $(VENV_DIR)
