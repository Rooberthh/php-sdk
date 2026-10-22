FROM node:22-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
  git \
  curl \
  jq \
  && rm -rf /var/lib/apt/lists/*

# Install GitHub CLI
RUN curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
  | dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
  | tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
  && apt-get update && apt-get install -y gh \
  && rm -rf /var/lib/apt/lists/*

# --- agent-kit: PHP + Composer -------------------------------------------
# PHP from the Sury repository so any 8.x version is available on bookworm.
ARG PHP_VERSION=8.4
RUN apt-get update && apt-get install -y ca-certificates curl unzip \
  && curl -fsSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
  && dpkg -i /tmp/debsuryorg-archive-keyring.deb && rm /tmp/debsuryorg-archive-keyring.deb \
  && echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ bookworm main" \
  > /etc/apt/sources.list.d/php.list \
  && apt-get update && apt-get install -y \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-gd \
  php${PHP_VERSION}-intl \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-pcov \
  php${PHP_VERSION}-pgsql \
  php${PHP_VERSION}-sqlite3 \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-zip \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# -------------------------------------------------------------------------

# Build-args for UID/GID alignment: sandcastle docker build-image
# defaults these to the host user's UID/GID so image-built files
# and bind-mounted files share an owner without runtime chown.
ARG AGENT_UID=1000
ARG AGENT_GID=1000

# Rename the base image's "node" user to "agent" and align UID/GID.
RUN groupmod -o -g $AGENT_GID node && usermod -o -u $AGENT_UID -g $AGENT_GID -d /home/agent -m -l agent node
USER ${AGENT_UID}:${AGENT_GID}

# Install Claude Code CLI
RUN curl -fsSL https://claude.ai/install.sh | bash

# Add Claude to PATH
ENV PATH="/home/agent/.local/bin:$PATH"

# --- agent-kit: Matt Pocock's skills -------------------------------------
# The host's Claude Code plugins don't reach the container, so install the
# skills plugin into the agent user's ~/.claude. The ADD busts Docker's cache
# whenever the skills repo gets a new commit, so `spinup build` updates them.
ADD https://api.github.com/repos/mattpocock/skills/git/refs/heads/main /tmp/skills-ref.json
RUN claude plugin marketplace add mattpocock/skills \
  && claude plugin install mattpocock-skills@mattpocock
# -------------------------------------------------------------------------

WORKDIR /home/agent

# In worktree sandbox mode, Sandcastle bind-mounts the git worktree at /home/agent/workspace
# and overrides the working directory to /home/agent/workspace at container start.
# Structure your Dockerfile so that /home/agent/workspace can serve as the project root.
ENTRYPOINT ["sleep", "infinity"]
