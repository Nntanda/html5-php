# GitHub Actions Workflow Quick Reference

## Single Workflow File
All CI/CD operations are in: `.github/workflows/ci-cd.yml`

## What Runs When

### On Every Push/PR to main or develop:
✅ Backend tests with MySQL  
✅ Backend security audit  
✅ Backend code quality checks  
✅ Admin app lint, type-check, and build  
✅ Admin app security audit  
✅ Client portal lint, type-check, and build  
✅ Client portal security audit  

### On Pull Requests Only:
✅ Dependency review (checks for vulnerable dependencies)

### On Push to main Branch:
✅ All of the above, plus:  
✅ Deploy job (creates production packages)  
✅ Uploads deployment artifacts

### Weekly (Sundays at midnight):
✅ Security scans for all components

### Manual Trigger:
✅ Can run workflow anytime from Actions tab

## Job Dependencies

```
All test/security jobs run in parallel
         ↓
    deploy job (only on main)
         ↓
    status-check (summary)
```

## Quick Commands

### View workflow status:
```bash
# In your repository
Go to: Actions tab → CI/CD Pipeline
```

### Manually trigger workflow:
```bash
# In GitHub UI
Actions → CI/CD Pipeline → Run workflow
```

### Download build artifacts:
```bash
# After successful main branch build
Actions → CI/CD Pipeline → Latest run → Artifacts section
```

## Environment Variables

Edit at top of `.github/workflows/ci-cd.yml`:
```yaml
env:
  PHP_VERSION: '8.2'
  NODE_VERSION: '20'
```

## Common Customizations

### Change test coverage threshold:
Find `backend-test` job → `Execute tests` step:
```yaml
run: php artisan test --coverage --min=70  # Change 70 to desired %
```

### Add deployment steps:
Find `deploy` job → Uncomment and customize deployment section

### Adjust security audit severity:
Find security jobs → Change `--audit-level=moderate` to `high` or `low`

## Artifacts Retention

- Development builds: 7 days
- Production builds: 30 days

## Status Badge

Add to README.md (replace YOUR_USERNAME and YOUR_REPO):
```markdown
![CI/CD Pipeline](https://github.com/YOUR_USERNAME/YOUR_REPO/workflows/CI%2FCD%20Pipeline/badge.svg)
```

## Troubleshooting

### Tests failing?
- Check MySQL connection in `backend-test` job
- Verify .env configuration
- Check PHP/Node versions

### Build failing?
- Check ESLint errors
- Verify TypeScript types
- Check for missing dependencies

### Deployment not running?
- Only runs on main branch
- Requires all tests to pass
- Check branch protection rules

## Need Help?

See full documentation: `docs/GITHUB_ACTIONS.md`
