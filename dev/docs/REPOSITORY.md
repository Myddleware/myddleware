# Repository

This document describes how manage Myddleware codebase, 
for sync this project from changes provided by UPSTREAM (https://github.com/Myddleware/myddleware) 
follow this page [UPSTREAM.md](UPSTREAM.md)

## Glossary

This word list used to shortening description of all operations

- **OCI:** Refers to our team OpenCrmItalia
- **UPSTREAM:** The main Myddleware repository you found it at <https://github.com/Myddleware/myddleware>
- **PR:** Shortening for Pull-Request    
- **PLEASE:** Follow instruction without any reply

## Branches Description

- `stable` is the default OCI branch, it contains the version for production environment and clients
- `master` is the branch used to sync updates from master of UPSTREAM
- `hotfix` is the branch used to sync updates from the hotfix of UPSTREAM
- `devops` this branch MUST be used to manage the repository for development operations (eg. clean branches)
- `contribute` this is the branch used from OCI for submit changes to UPSTREAM via PR

## NDT: Never do this

This is the list of NDT operation, never do this operation (PLEASE):

- Never merge the `contribute` branch into `master` or any other branches, this is only for PR to UPSTREAM 
