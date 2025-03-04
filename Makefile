# Makefile for Laravel Project
# Define versions
PHP_VERSION = 8.2

# Detect the operating system
UNAME_S := $(shell uname -s)
OS := $(shell \
	if [ "$(UNAME_S)" = "Darwin" ]; then \
		echo "mac"; \
	elif [ "$(UNAME_S)" = "Linux" ]; then \
		bash -c 'source /etc/os-release 2>/dev/null && echo $$ID || echo "linux"'; \
	elif [ "$(UNAME_S)" = "MINGW64_NT-10.0" ] || [ "$(UNAME_S)" = "MSYS_NT-10.0" ]; then \
		echo "windows"; \
	else \
		echo "unknown"; \
	fi \
)

# Declare phony targets to ensure they always run
.PHONY: help install update test serve os-check

# Default target
all: install update test

# Help target to show available commands
help:
	@echo "Cross-Platform Laravel Project Makefile"
	@echo "======================================="
	@echo "Usage: make [target] [OS=mac|windows|ubuntu]"
	@echo ""
	@echo "Available targets:"
	@echo " install - Install system and Laravel dependencies"
	@echo " update  - Update installed dependencies"
	@echo " test    - Run Laravel tests"
	@echo " serve   - Start the Laravel application"
	@echo ""
	@echo "Optional OS specification:"
	@echo " make install OS=mac      (for macOS)"
	@echo " make install OS=windows  (for Windows)"
	@echo " make install OS=ubuntu   (for Ubuntu Linux)"

# OS Detection and Validation
os-check:
	@echo "🖥️  Detected OS: $(OS)"
	@if [ "$(OS)" = "unknown" ]; then \
		echo "❌ Unsupported or unspecified OS. Use: make help"; \
		exit 1; \
	fi

# Install dependencies based on OS
install: os-check
	@echo "🔍 Installing dependencies for $(OS)..."
	
	# macOS Installation
	@if [ "$(OS)" = "mac" ]; then \
		echo "🍎 Setting up for macOS..."; \
		if ! command -v brew &> /dev/null; then \
			echo "Installing Homebrew..."; \
			/bin/bash -c "$$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"; \
		fi; \
		brew update; \
		brew install php@$(PHP_VERSION) composer npm; \
	fi
	
	# Windows Installation (using Windows Subsystem for Linux or Chocolatey)
	@if [ "$(OS)" = "windows" ]; then \
		echo "🪟 Setting up for Windows..."; \
		echo "Please ensure you have Windows Subsystem for Linux (WSL) or use Chocolatey:"; \
		echo "1. For WSL: Install Ubuntu from Microsoft Store"; \
		echo "2. For Chocolatey:"; \
		echo "   - Install Chocolatey (https://chocolatey.org/install)"; \
		echo "   - Run: choco install php composer nodejs npm"; \
	fi
	
	# Ubuntu Linux Installation
	@if [ "$(OS)" = "ubuntu" ]; then \
		echo "🐧 Setting up for Ubuntu Linux..."; \
		sudo apt update; \
		sudo apt install -y software-properties-common; \
		sudo add-apt-repository ppa:ondrej/php -y; \
		sudo apt update; \
		sudo apt install -y php$(PHP_VERSION) php-cli php-mbstring \
			php-xml php-bcmath php-curl php-tokenizer php-mysql \
			unzip curl git composer nodejs npm; \
	fi
	
	@echo "🔧 Installing Laravel dependencies..."
	@composer install --no-interaction --prefer-dist
	@npm install --silent
	@echo "✅ Installation complete for $(OS)!"

# Rest of the targets remain the same as in your original Makefile
update: os-check
	@echo "🔄 Updating dependencies for $(OS)..."
	@if [ "$(OS)" = "mac" ]; then \
		brew update && brew upgrade php@$(PHP_VERSION) composer npm; \
	fi
	@if [ "$(OS)" = "ubuntu" ]; then \
		sudo apt update && sudo apt upgrade -y; \
	fi
	@composer update --no-interaction
	@npm update --silent
	@echo "✅ Update complete for $(OS)!"

test:
	@echo "🧪 Running Laravel tests..."
	@php artisan test

serve:
	@echo "🚀 Starting Laravel development server..."
	@php artisan serve --host=0.0.0.0 --port=8000
