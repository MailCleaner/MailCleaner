export PYENV_ROOT="/var/mailcleaner/.pyenv"
export PATH="$PYENV_ROOT/bin:$PATH"
export PYENV_VERSION="3.7.7"
if command -v pyenv 1>/dev/null 2>&1; then
  eval "$(pyenv init --path)"
fi
