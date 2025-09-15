# DDEV and Docksal Switching Guide

## Overview

DDEV and Docksal cannot run simultaneously as they both use Docker and may compete for ports and resources. This guide provides the commands needed to properly switch between the two development environments.

## Stopping All DDEV Projects

### Primary Command (Recommended)
```bash
ddev poweroff
```
This stops all DDEV projects and removes the shared services (router, DNS, etc.).

### Alternative Command
```bash
ddev stop --all
```
Note: `ddev poweroff` is more thorough as it also stops the global DDEV services.

## Stopping All Docksal Projects

### Primary Command (Recommended)
```bash
fin system stop
```
This stops all Docksal projects and the system services.

### Alternative Command
```bash
fin stop --all
```
Note: `fin system stop` is more comprehensive.

## Switching Workflows

### From Docksal to DDEV
```bash
fin system stop
ddev start
```

### From DDEV to Docksal
```bash
ddev poweroff
fin start
```

## Additional Tips

- **Check running containers**: Use `docker ps` to see all active containers
- **Port conflicts**: If you encounter port conflicts, you might need to restart Docker Desktop/daemon
- **Coexistence**: Both tools have configuration options to use different ports if you need them to coexist, though this is more complex
- **Clean switching**: The `poweroff` and `system stop` commands are your best bet for cleanly switching between the two systems

## Quick Reference

| Action | Command |
|--------|---------|
| Stop all DDEV | `ddev poweroff` |
| Stop all Docksal | `fin system stop` |
| Check Docker containers | `docker ps` |

---

*This guide ensures clean switching between DDEV and Docksal development environments without conflicts.*