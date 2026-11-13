# WordPress dummy

Local WordPress for this plugin. It is the WordPress counterpart of the Rails app in `test/dummy/`. The site is `@wordpress/env` (`wp-env`), which this repo already uses to run WordPress.

From the repository root:

```bash
bin/wordpress-dummy
```

That mounts `wordpress/recording-studio-widgets/` as an active plugin, then runs `wordpress/dummy/bin/seed.sh`.

| | |
| --- | --- |
| Site | http://localhost:8888 |
| Sample page | http://localhost:8888/studio-widget/ |
| Admin | http://localhost:8888/wp-admin |
| Username | `admin` |
| Password | `password` |
| Email | `dev-admin@example.test` |

`admin` / `password` is the wp-env dev login. The seed sets the email on every start. Use it only on this local site.

PHP in the container calls the Rails dummy at `http://host.docker.internal:3000`. The constants are `RECORDING_STUDIO_HOST_BASE_URL` and `RECORDING_STUDIO_CLIENT_ID` in `.wp-env.json`. The client id is `rsoauth_id_wordpress`. `wp-env` 11.15.0 maps `host.docker.internal` with `extra_hosts: ["host.docker.internal:host-gateway"]`. A custom Compose file needs that `extra_hosts` entry, and `docker run` needs `--add-host=host.docker.internal:host-gateway`. On Linux the browser also needs `127.0.0.1 host.docker.internal` in `/etc/hosts`. `WORDPRESS_DUMMY_CHECK_RAILS=1 bin/wordpress-dummy-smoke` curls the host URL from inside the container. It is off unless you set it.

The sample page contains the **WP Template Demo** block with stand-in page recording id `11111111-1111-4111-8111-111111111111`. Before Connect, the page shows “Connect this site under Settings → WP Template Demo.”

Full steps (start the Rails dummy, start WordPress, connect, insert the block, view the front end) are in the repository README under “Start WordPress with wp-env”.
