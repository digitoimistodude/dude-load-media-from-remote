# Load images from remote for Dudestack

A WordPress plugin that tries to load images from remote url if file is not present in local environment.

# Clean up the media

### Set up media load from production

It's nice to have images if you need to debug or develop the site further, without having to copy all images to your computer or sync them with Syncthing.

Add [dude-load-media-from-remote](https://github.com/digitoimistodude/dude-load-media-from-remote) to repositories section in your composer.json:

```json
{
  "type": "vcs",
  "url": "https://github.com/digitoimistodude/dude-load-media-from-remote"
}
```

Then add to require-dev in your composer.json:

```json
"digitoimistodude/dude-load-media-from-remote": "dev-main"
```

Then modify `.env` file and add:

```bash
REMOTE_MEDIA_URL=https://production.fi
```

Run:

```bash
composer update
```

Then run:

```sh
wp plugin activate dude-load-media-from-remote
```

## Disclaimer

This plugin is made to use with [dudestack](https://github.com/digitoimistodude/dudestack) and [air-helper](https://github.com/digitoimistodude/air-helper), but it might work without it too, since it only alters the url. Notice that the url structure and image sizes must be exactly the same in the remote env.
