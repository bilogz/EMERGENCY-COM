# Remote Database Connection Setup

## Problem
Remote MySQL server is refusing connections from localhost development machine.

## Solution Options

### Option 1: Allow Remote Connections on MySQL Server

On the remote server (`alertaraqc.com`), run these SQL commands in MySQL:

```sql
-- Allow root user to connect from any host (less secure)
GRANT ALL PRIVILEGES ON emer_comm_test.* TO 'root'@'%' IDENTIFIED BY 'YsqnXk6q#145';
FLUSH PRIVILEGES;

-- OR allow from specific IP (more secure)
-- Replace YOUR_IP with your actual IP address
GRANT ALL PRIVILEGES ON emer_comm_test.* TO 'root'@'YOUR_IP' IDENTIFIED BY 'YsqnXk6q#145';
FLUSH PRIVILEGES;
```

Also check MySQL configuration file (`my.cnf` or `my.ini`):
```ini
bind-address = 0.0.0.0  # Allow connections from any IP
# OR
bind-address = YOUR_SERVER_IP  # Allow from specific IP
```

Then restart MySQL service.

### Option 2: Deploy Code to Remote Server

Upload your code to `alertaraqc.com` server. When PHP runs on the remote server, `localhost` will connect to the remote MySQL database.

### Option 3: Use SSH Tunnel (Advanced)

Create an SSH tunnel to forward MySQL port:
```bash
ssh -L 3307:localhost:3306 user@alertaraqc.com
```

Then connect to `localhost:3307` which will tunnel to remote MySQL.

### Option 4: Temporary - Disable Localhost Fallback

For testing, we can remove localhost fallback so it fails loudly instead of silently using localhost.

