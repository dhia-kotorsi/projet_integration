
pipeline {
    agent any

    options {
        timestamps()
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Validate application files') {
            steps {
                sh '''
                    set -eu
                    test -f Jenkinsfile

                    find . -name 'api.php' -type f -print
                    find . -name 'Dockerfile' -type f -print
                    find . \\( -name 'docker-compose.yml' -o -name 'compose.yml' \\) -type f -print

                    test "$(find . -name 'api.php' -type f | wc -l)" -gt 0
                    test "$(find . -name 'Dockerfile' -type f | wc -l)" -gt 0
                '''
            }
        }

        stage('PHP syntax check') {
            steps {
                sh '''
                    set -eu
                    find . -type f -name '*.php' \
                        ! -name 'config.php' \
                        -exec php -l {} \\;
                '''
            }
        }
    }

    post {
        success {
            echo 'CI pipeline completed successfully!'
        }
        failure {
            echo 'CI pipeline failed. Check the console output.'
        }
    }
}
