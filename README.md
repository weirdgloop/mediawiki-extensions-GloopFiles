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
| align | Alignment of the container on the page | center |
| caption | Add a caption | null |
| autostart | Autoload the 3D viewer instead of the thumbnail | false |
| alt | Alt text to display if the model doesn't load | `<gloopfiles-mview-alt>` |