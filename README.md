# GloopFiles
## Description
Provides support for additional file types for Weird Gloop wikis.


## Supported file extensions

### .mview
Marmoset viewer based 3D models


Parameters:

| Name | Description | Default value |
| ---- | ----------- | ------------- |
| width | Canvas width in pixels | 500 or `$wgGloopFilesConfig["width"]` |
| height | Canvas height in pixels | 500 or `$wgGloopFilesConfig["height"]` |
| align | Alignment of the container on the page | null |
| caption | Add a caption | null |
| autostart | Autoload the 3D viewer instead of the thumbnail | false |
| background | Toggles the background to the viewer to the opposite of `$wgGloopFilesViewerConfig["transparent"]` [^1][^2]| false |
| userinterface | Toggles the user interface to the opposite of `$wgGloopFilesViewerConfig["userinterface"]` [^1][^2]| false |

[^1]: These settings require the corresponding `overridebg` or `overridebg` settings to be enabled.
[^2]: Note that do to how the viewer library is written these toggles effect every instance on a page.


Javascript hooks:
The following hooks (`mw.hook()`) are emited by the viewer:

* `gloopfiles.ready` - When all viewer instances found on the page have been initialised.
* `gloopfiles.sceneloaded` - Each time a scene is loaded (started) either from the UI or via the `loadScene()` function.


Javascript functions:
The viewer object is a global named `mw.gloopfiles_mview` and has the following functions available.

* `addNew()` - Checks the page for viewer divs and initialises any that aren't yet. Use when loading content dynamically or for example with tabbers.
* `toggleBG()` - Toggles the background transparency of all viewer instances.
* `toggleUI()` - Toggles the UI for all viewer instances.


## Configuration
As with any other extension install and use it by uploading it to the extensions directory and adding `wfLoadExtension( 'GloopFiles' );` to your settings.

### Default size of viewer
```php
$wgGloopFilesConfig = [
	'width' => 450,
	'height' => 450
];
```


### Viewer settings
```php
$wgGloopFilesViewerConfig = [
	'autolimit' => 2,			// Maximum bumber of viewers to autostart
	'activelimit' => 5,			// Maximum number of active viewers
	'transparent' => true,		// Use a transparent background on viewers
	'overridebg' => true,		// Allow overriding the background (transparent or not) per page
	'userinterface' => true,	// Defaul to having a visible user interface
	'overrideui' => true		// Allow overriding the UI per page
];
```


### File upload
Do not forget too add `.mview` as an allowed upload file type:
```php
$wgFileExtensions[] = 'mview';
```


## Known issues
* Currently not compatible with slideshow type galleries - would require a major rework of the slideshow js.
* Currently scenes aren't resized correctly in packed type galleries.