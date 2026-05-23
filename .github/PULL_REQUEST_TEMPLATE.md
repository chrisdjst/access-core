## Summary

<!--
One or two sentences: what changes and why. Link related issues with `Closes #N` or `Fixes #N`.
-->

## Changes

<!--
- Bullet list of the meaningful changes (skip trivial reformatting).
- Note new ports, use-cases, value objects, or breaking changes here.
-->

## Test plan

<!--
How did you verify the change? Tick what applies:

- [ ] `composer test` — all green locally
- [ ] `composer stan` — PHPStan level 8 clean
- [ ] Added new tests covering the change
- [ ] Manual scenario (describe)
-->

## Breaking changes

<!--
List any changes to public domain or port contracts.
Leave the section empty if there are none.
-->

## Checklist

- [ ] Diff is focused; unrelated changes split into other PRs
- [ ] CHANGELOG `Unreleased` section updated if the change is user-visible
- [ ] PHPdoc on new public methods / value objects
- [ ] No `use Illuminate\…` introduced (this package is framework-agnostic)
