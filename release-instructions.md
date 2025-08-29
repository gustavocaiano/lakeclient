# Release Instructions (lakeclient)

## Prerequisites
- Ensure tests pass locally: `composer test`
- Ensure code style and static analysis pass: `composer format` and `composer analyse`
- Update `CHANGELOG.md`
- Commit all changes

## Versioning
- Follow SemVer: MAJOR.MINOR.PATCH
- Bump version in `CHANGELOG.md` heading and commit it

## Tagging a release on Git
```bash
# From the lakeclient directory root
git pull
# Replace 0.1.0 with your version
git tag -a v0.1.0 -m "Release v0.1.0"
git push origin v0.1.0
```

## Create a GitHub Release
- Go to GitHub repo `gustavocaiano/lakeclient`
- Draft a new release targeting tag `vX.Y.Z`
- Paste highlights from `CHANGELOG.md`
- Publish

## Packagist (if publishing)
- If package is on Packagist, ensure the repository is linked
- Packagist will auto-fetch tags; otherwise click "Update" in Packagist

## Post-release checks
- In a fresh Laravel app:
```json
{
  "require": {
    "gustavocaiano/lakeclient": "^0.1.0"
  }
}
```
- Then `composer update gustavocaiano/lakeclient`
- Follow README Usage to integrate

## Hotfix release
- Commit fixes
- Bump PATCH in `CHANGELOG.md`
- Tag and push: `vX.Y.Z+1`
- Update GitHub release notes if needed
