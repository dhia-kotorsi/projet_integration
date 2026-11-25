
pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    environment {
        ANSIBLE_DIR = '/home/vagrant/ansible'
        APP_URL = 'http://192.168.56.11:8080/'
    }

    stages {

        stage('Checkout') {
            steps {
                echo 'Downloading application from GitHub...'
                checkout scm
            }
        }

        stage('Validate Files') {
            steps {
                echo 'Checking application structure...'

                sh '''
                    set -eu

                    test -f application/docker-compose.yml
                    test -f application/Dockerfile
                    test -f application/nginx/default.conf
                    test -f application/src/api.php
                    test -f application/src/config.php
                    test -f application/src/index.html

                    echo "All required application files exist."
                '''
            }
        }

        stage('PHP Syntax Check') {
            steps {
                echo 'Checking PHP syntax...'

                sh '''
                    set -eu

                    php -l application/src/api.php
                    php -l application/src/config.php

                    echo "PHP syntax validation passed."
                '''
            }
        }

        stage('Deploy with Ansible') {
            

            steps {
                echo 'Deploying application to VM1...'

                withCredentials([
                    string(
                        credentialsId: 'ansible-vault-password',
                        variable: 'VAULT_PASS'
                    )
                ]) {
                    sh '''
                        set +x
                        set -eu

                        umask 077
                        VAULT_FILE="$(mktemp)"
                        trap 'rm -f "$VAULT_FILE"' EXIT

                        printf '%s' "$VAULT_PASS" > "$VAULT_FILE"

                        ansible-playbook \
                          -i "$ANSIBLE_DIR/inventory.ini" \
                          "$ANSIBLE_DIR/playbooks/deploy_php_application.yml" \
                          --private-key /var/lib/jenkins/.ssh/id_ed25519 \
                          --vault-password-file "$VAULT_FILE" \
                          -e "application_source=$WORKSPACE/application"

                        echo "Ansible deployment completed."
                    '''
                }
            }
        }

        stage('Health Check') {
            when {
                branch 'main'
            }

            steps {
                echo 'Checking deployed application...'

                sh '''
                    set -eu

                    curl --fail --silent --show-error \
                         --retry 5 \
                         --retry-delay 3 \
                         --retry-connrefused \
                         "$APP_URL" > /dev/null

                    echo "Application is responding successfully."
                '''
            }
        }
    }

    post {
        success {
            echo 'CI/CD pipeline completed successfully!'
        }

        failure {
            echo 'CI/CD pipeline failed. Check Console Output.'
        }

        always {
            echo 'Pipeline execution finished.'
        }
    }
}
