# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.3] - 2018-03-31
### Added
- Added `Simple` Spotter, for passing simple sets of arrays to a Climber instance. In theory this helps deal with providers that don't have their own Spotter (and you don't want to write a Spotter for them).

## [0.2.2] - 2018-03-31
### Added
- Added the `baseClass` property and `compileClass` method to `Climber`. Adjusted the names of the various classes to work with these. This allows the user to modify the base CSS classes used to generate all the rest of the classes with a single line.

## [0.2.1] - 2018-03-30
### Added
- Added `element` data part to most filters, allowing users to directly modify the `sprintf` template used to generate the element in questions.

## [0.2.0] - 2018-03-28
### Added
- `Surveyor` class to allow for regex-based URL matching (for manually setting 'current' URL).
- `Climber::setCurrentUrl()` method to manually set active URL after instantiation. 
- `Climber::isActivated()` method to determine if any leaf is set active.
- Better testing for everything.
- Convenience functions (see README.md).
- "API"s for all classes in the form of interfaces. Hopefully this will improve backward-compatibility.
- `Tree::subtree()` method to create subtrees (sub-sections of existing trees).
- `Forester` class (extends `Spotter`) to feed Trees back into themselves (currently used when creating subtrees).

### Changed
- Refactored system for setting active URL on Tree so that it is more accessible.
- Improved behavior of "activation" system.

## [0.1.0] - 2018-01-26
### Added
- All basic functionality.