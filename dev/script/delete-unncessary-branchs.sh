#!/usr/bin/env bash
set -e

git pull
for branch in $( git for-each-ref --format='%(refname:lstrip=3)' refs/remotes/origin/); do
    [[ "$branch" == "develop" ]] && git push origin --delete $branch
    [[ "$branch" == "upstream" ]] && git push origin --delete $branch
    [[ "$branch" =~ "analysis-"* ]] && git push origin --delete $branch
    #[[ "$branch" == "Release-2.5.2" ]] && git push origin --delete $branch
    #[[ "$branch" == "release-v2.4.2" ]] && git push origin --delete $branch
done

echo "Done."
