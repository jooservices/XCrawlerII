#!/usr/bin/env bash
set -euo pipefail

OS_ID=""
OS_VERSION_ID=""
OS_CODENAME=""
ARCH=""

log() {
  echo "[install] $*"
}

fail() {
  echo "[install][error] $*" >&2
  exit 1
}

require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    fail "Run as root (or with sudo): sudo bash $0"
  fi
}

detect_platform() {
  if [[ ! -f /etc/os-release ]]; then
    fail "/etc/os-release not found; cannot detect OS"
  fi

  # shellcheck disable=SC1091
  source /etc/os-release

  OS_ID="${ID:-}"
  OS_VERSION_ID="${VERSION_ID:-}"
  OS_CODENAME="${VERSION_CODENAME:-}"
  ARCH="$(dpkg --print-architecture 2>/dev/null || uname -m)"

  if [[ -z "${OS_ID}" || -z "${OS_VERSION_ID}" || -z "${OS_CODENAME}" ]]; then
    fail "Unable to detect OS ID/version/codename from /etc/os-release"
  fi

  case "${OS_ID}" in
    ubuntu|debian)
      ;;
    *)
      fail "Unsupported OS '${OS_ID}'. Supported: ubuntu, debian"
      ;;
  esac

  log "Detected platform: ${OS_ID} ${OS_VERSION_ID} (${OS_CODENAME}), arch=${ARCH}"
}

setup_php_repo() {
  if [[ "${OS_ID}" == "ubuntu" ]]; then
    log "Configuring Ondrej PHP PPA for Ubuntu (${OS_CODENAME})"
    apt-get update -y
    DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common ca-certificates lsb-release gnupg2 apt-transport-https

    if ! grep -R "ppa.launchpadcontent.net/ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d >/dev/null 2>&1; then
      add-apt-repository -y ppa:ondrej/php
    fi

    apt-get update -y
    return
  fi

  if [[ "${OS_ID}" == "debian" ]]; then
    log "Configuring Sury PHP repo for Debian (${OS_CODENAME})"
    apt-get update -y
    DEBIAN_FRONTEND=noninteractive apt-get install -y ca-certificates curl gnupg2 lsb-release apt-transport-https

    install -d -m 0755 /etc/apt/keyrings
    if [[ ! -f /etc/apt/keyrings/sury.gpg ]]; then
      curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/keyrings/sury.gpg
    fi

    cat >/etc/apt/sources.list.d/php-sury.list <<EOF
# Sury PHP repository
deb [signed-by=/etc/apt/keyrings/sury.gpg] https://packages.sury.org/php/ ${OS_CODENAME} main
EOF

    apt-get update -y
    return
  fi
}

install_php_packages() {
  log "Installing PHP 8.5 base packages"

  local packages=(
    php8.5-cli
    php8.5-fpm
    php8.5-common
    php8.5-bcmath
    php8.5-curl
    php8.5-dom
    php8.5-gd
    php8.5-intl
    php8.5-mbstring
    php8.5-mysql
    php8.5-opcache
    php8.5-readline
    php8.5-xml
    php8.5-zip
    php8.5-sqlite3
    php8.5-dev
    php-pear
    build-essential
    pkg-config
    autoconf
    unzip
    git
    curl
  )

  DEBIAN_FRONTEND=noninteractive apt-get install -y "${packages[@]}"

  if command -v php >/dev/null 2>&1; then
    log "PHP installed: $(php -v | head -n 1)"
  fi

  if command -v php-fpm8.5 >/dev/null 2>&1; then
    systemctl enable php8.5-fpm >/dev/null 2>&1 || true
  fi
}

apt_install_if_available() {
  local package_name="${1}"

  if apt-cache show "${package_name}" >/dev/null 2>&1; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y "${package_name}"
    return 0
  fi

  return 1
}

enable_extension_ini() {
  local module_name="${1}"
  local ini_file="/etc/php/8.5/mods-available/${module_name}.ini"

  if [[ ! -f "${ini_file}" ]]; then
    printf "extension=%s.so\n" "${module_name}" >"${ini_file}"
  fi

  if command -v phpenmod >/dev/null 2>&1; then
    phpenmod -v 8.5 "${module_name}" || true
  fi
}

install_extension_via_pecl() {
  local module_name="${1}"

  if php -m | grep -iq "^${module_name}$"; then
    log "PHP extension '${module_name}' already enabled"
    return
  fi

  log "Installing '${module_name}' via PECL (fallback)"
  yes '' | pecl install -f "${module_name}"
  enable_extension_ini "${module_name}"
}

install_extension_via_pecl_best_effort() {
  local module_name="${1}"

  if ! command -v pecl >/dev/null 2>&1; then
    return 1
  fi

  if php -m | grep -iq "^${module_name}$"; then
    log "PHP extension '${module_name}' already enabled"
    return 0
  fi

  log "Attempting to install '${module_name}' via PECL"
  if yes '' | pecl install -f "${module_name}"; then
    enable_extension_ini "${module_name}"
    return 0
  fi

  return 1
}

install_php_queue_extensions() {
  log "Installing redis and mongodb PHP extensions"

  if ! install_extension_via_pecl_best_effort "redis"; then
    log "PECL redis unavailable/failed, falling back to APT package"
    if ! apt_install_if_available "php8.5-redis"; then
      fail "Unable to install redis extension via PECL or APT"
    fi
  fi
  if ! php -m | grep -iq '^redis$'; then
    fail "Redis extension is still not enabled after installation attempts"
  fi

  if ! apt_install_if_available "php8.5-mongodb"; then
    log "Package php8.5-mongodb not found in repositories"
  fi
  if ! php -m | grep -iq '^mongodb$'; then
    install_extension_via_pecl "mongodb"
  fi

  if command -v php-fpm8.5 >/dev/null 2>&1; then
    systemctl restart php8.5-fpm >/dev/null 2>&1 || true
  fi
}

install_composer() {
  if command -v composer >/dev/null 2>&1; then
    log "Composer already installed: $(composer --version)"
    return
  fi

  log "Installing Composer"
  local installer checksum expected
  installer="/tmp/composer-setup.php"

  checksum="$(curl -fsSL https://composer.github.io/installer.sig)"
  curl -fsSL https://getcomposer.org/installer -o "${installer}"
  expected="$(php -r "echo hash_file('sha384', '${installer}');")"

  if [[ "${checksum}" != "${expected}" ]]; then
    rm -f "${installer}"
    fail "Composer installer checksum verification failed"
  fi

  php "${installer}" --install-dir=/usr/local/bin --filename=composer
  rm -f "${installer}"

  log "Composer installed: $(composer --version)"
}

main() {
  require_root

  detect_platform

  if [[ "${OS_ID}" == "ubuntu" && "${OS_VERSION_ID}" == "24.04" ]]; then
    log "Target OS Ubuntu 24.04 confirmed"
  fi

  setup_php_repo
  install_php_packages
  install_php_queue_extensions
  install_composer

  log "Done."
  log "Quick checks:"
  log "  php -v"
  log "  php -m | egrep 'redis|mongodb|mbstring|xml|curl|zip|pdo_mysql|bcmath|intl'"
  log "  composer --version"
}

main "$@"
