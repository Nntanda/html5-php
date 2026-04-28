# GitHub Actions CI/CD Documentation

This document describes the unified GitHub Actions workflow configured for the SACCO Management System.

## Single Workflow Architecture

All CI/CD operations are consolidated into a single workflow file: `.github/workflows/ci-cd.yml`

This approach provides:
- Easier maintenance and configuration
- Better visibility of the entire pipeline
- Simplified dependency management between jobs
- Reduced duplication
- Single status badge for the entire pipeline

## Workflow Overview

**File:** `.github/workflows/ci-cd.yml`

**Triggers:**
- Push to main/develop branches
- Pull requests to main/develop branches
- Weekly schedule (Sundays at midnight) for security scans
- Manual workflow dispatch

**Environment Variables:**
- `PHP_VERSION: 8.2`
- `NODE_VERSION: 20`

## Jobs Breakdown

### Backend Jobs

#### 1. backend-test
- Sets up PHP 8.2 with required extensions
- Configures MySQL 8.0 test database
- Caches Composer dependencies
- Runs Laravel migrations
- Executes PHPUnit tests with 70% minimum coverage
- Uploads coverage reports to Codecov

#### 2. backend-security
- Runs `composer audit` for dependency vulnerabilities
- Checks for known security issues in PHP packages

#### 3. backend-quality
- Runs PHP CodeSniffer (if phpcs.xml exists)
- Runs PHPStan static analysis (if phpstan.neon exists)
- Continues on error to not block pipeline

### Admin App Jobs

#### 4. admin-app-test
- Sets up Node.js 20 with npm caching
- Installs dependencies with npm ci
- Runs ESLint for code quality
- Performs TypeScript type checking
- Builds production bundle
- Uploads build artifacts (main branch only)

#### 5. admin-app-security
- Runs `npm audit` with moderate severity threshold
- Checks for known vulnerabilities in dependencies

### Client Portal Jobs

#### 6. client-portal-test
- Sets up Node.js 20 with npm caching
- Installs dependencies with npm ci
- Runs ESLint for code quality
- Performs TypeScript type checking
- Builds production bundle
- Uploads build artifacts (main branch only)

#### 7. client-portal-security
- Runs `npm audit` with moderate severity threshold
- Checks for known vulnerabilities in dependencies

### Additional Jobs

#### 8. dependency-review
- Runs only on pull requests
- Reviews dependency changes for security issues
- Uses GitHub's dependency review action

#### 9. deploy
- Runs only on main branch pushes
- Requires all test and security jobs to pass
- Creates optimized deployment packages for all components
- Uploads artifacts with 30-day retention
- Ready for custom deployment steps

#### 10. status-check
- Final job that checks all previous jobs
- Provides clear pass/fail status for the entire pipeline
- Useful for branch protection rules

## Setup Instructions

### 1. Repository Secrets
Configure these secrets in your GitHub repository settings:

```
Settings → Secrets and variables → Actions → New repository secret
```

**Optional secrets for deployment:**
- `DEPLOY_HOST`: Production server hostname
- `DEPLOY_USER`: SSH username
- `DEPLOY_KEY`: SSH private key
- `DEPLOY_PATH`: Deployment directory path

### 2. Environment Configuration

Create a production environment:
```
Settings → Environments → New environment → "production"
```

Add environment-specific secrets:
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `APP_KEY`

### 3. Branch Protection Rules

Recommended settings for `main` branch:
```
Settings → Branches → Add rule
```

- Require pull request reviews before merging
- Require status checks to pass:
  - Backend Tests
  - Admin App Build & Test
  - Client Portal Build & Test
  - Backend Security Audit
  - Admin App Security Audit
  - Client Portal Security Audit
- Require branches to be up to date before merging

### 4. Codecov Integration (Optional)

1. Sign up at https://codecov.io
2. Add your repository
3. Add `CODECOV_TOKEN` to repository secrets
4. Coverage reports will be automatically uploaded

## Customizing the Workflow

### Adding Deployment Steps

Edit `.github/workflows/ci-cd.yml` in the `deploy` job and uncomment/customize the deployment section:

**SSH Deployment Example:**
```yaml
- name: Deploy to Server
  uses: appleboy/ssh-action@master
  with:
    host: ${{ secrets.DEPLOY_HOST }}
    username: ${{ secrets.DEPLOY_USER }}
    key: ${{ secrets.DEPLOY_KEY }}
    script: |
      cd ${{ secrets.DEPLOY_PATH }}
      tar -xzf backend-deploy.tar.gz
      php artisan migrate --force
      php artisan config:cache
      php artisan route:cache
```

**AWS S3 Frontend Deployment:**
```yaml
- name: Deploy Admin App to S3
  uses: jakejarvis/s3-sync-action@master
  with:
    args: --delete
  env:
    AWS_S3_BUCKET: ${{ secrets.AWS_S3_BUCKET }}
    AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
    AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
    SOURCE_DIR: 'admin-app/dist'
```

### Changing PHP or Node.js Versions

Edit the environment variables at the top of `.github/workflows/ci-cd.yml`:
```yaml
env:
  PHP_VERSION: '8.3'  # Change PHP version
  NODE_VERSION: '21'  # Change Node.js version
```

### Adjusting Coverage Thresholds

Edit `.github/workflows/ci-cd.yml` in the `backend-test` job:
```yaml
- name: Execute tests
  working-directory: backend
  run: php artisan test --coverage --min=80  # Change from 70 to 80
```

### Adding Matrix Builds

To test multiple versions, add a matrix strategy:
```yaml
backend-test:
  strategy:
    matrix:
      php-version: ['8.1', '8.2', '8.3']
  steps:
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
```

## Workflow Status Badge

Add this to your README.md:

```markdown
![CI/CD Pipeline](https://github.com/YOUR_USERNAME/YOUR_REPO/workflows/CI%2FCD%20Pipeline/badge.svg)
```

This single badge shows the status of the entire pipeline.

## Troubleshooting

### MySQL Connection Issues
If tests fail with database connection errors:
- Check MySQL service health check configuration
- Verify database credentials in workflow
- Ensure migrations run before tests

### Node.js Build Failures
- Dependencies are cached automatically
- Check Node.js version compatibility
- Verify all dependencies are in package.json
- Clear cache by updating cache key

### PHP Extension Missing
Add to `shivammathur/setup-php@v2`:
```yaml
extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, json, bcmath, YOUR_EXTENSION
```

### Timeout Issues
Increase timeout for long-running jobs:
```yaml
jobs:
  backend-test:
    timeout-minutes: 30  # Default is 360
```

### Cache Issues
If caching causes problems, you can disable it:
```yaml
# Remove or comment out the cache step
# - name: Cache Composer dependencies
#   uses: actions/cache@v3
```

## Best Practices

1. **Keep workflows fast**: Dependencies are cached automatically
2. **Fail fast**: Linting and type checks run before builds
3. **Parallel execution**: All jobs run in parallel when possible
4. **Conditional deployment**: Only deploys on main branch after all tests pass
5. **Artifacts**: Build artifacts retained for 7 days (dev) or 30 days (production)
6. **Security**: Never commit secrets, use GitHub Secrets
7. **Notifications**: Configure Slack/email notifications for failures

## Advantages of Single Workflow

1. **Easier to maintain**: One file to update instead of multiple
2. **Better dependency management**: Clear job dependencies with `needs:`
3. **Consistent environment**: Shared environment variables
4. **Simpler status checks**: One workflow to monitor
5. **Reduced duplication**: Shared steps and configurations
6. **Better visibility**: See entire pipeline in one view
7. **Single badge**: One status badge for the entire pipeline

## Monitoring

View workflow runs:
```
Repository → Actions tab
```

- Click on the workflow to see all jobs
- Expand each job to see detailed logs
- Download artifacts from successful builds
- Re-run failed jobs individually or entire workflow
- Cancel running workflows

## Cost Optimization

GitHub Actions is free for public repositories. For private repositories:
- 2,000 minutes/month free for private repos
- Caching reduces build time significantly
- Jobs run in parallel to minimize total time
- Use self-hosted runners for unlimited minutes

## Notifications

### Slack Notifications

Add to the end of the workflow:
```yaml
- name: Slack Notification
  if: failure()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    text: 'CI/CD Pipeline failed!'
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### Email Notifications

Configure in GitHub Settings:
```
Settings → Notifications → Actions
```

## Next Steps

1. Push the workflow to your repository
2. Configure required secrets
3. Set up branch protection rules
4. Test the workflow with a pull request
5. Customize deployment steps for your infrastructure
6. Add status badge to README.md
7. Configure notifications for your team

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Vite Build Configuration](https://vitejs.dev/guide/build.html)
- [Codecov Documentation](https://docs.codecov.com/)
