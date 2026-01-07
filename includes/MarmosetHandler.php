<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extensions\GloopFiles;

use MediaHandler;
use MediaWiki\FileRepo\File\File;
use MediaWiki\Shell\Shell;

class MarmosetHandler extends MediaHandler {
    public const MVIEW_METADATA_VERSION = 1;

    /**
     * @return array
     */
    public function getParamMap() {
        return [
            'img_width' => 'width',
            'img_height' => 'height',
            'model_autostart' => 'autostart',
            'img_thumb' => 'thumbnailurl',
            'img_bg' => 'background',
            'img_ui' => 'userinterface'
        ];
    }

    /**
     * @param  string $name
     * @param  string $value
     * @return bool
     */
    public function validateParam( $name, $value ) {
        if ( in_array( $name, [ 'width', 'height' ] ) ) {
            if ( $value <= 0 ) {
             return false;
            } else {
             return true;
            }
        } elseif ( $name == 'thumbnailurl' ) {
            if ( mb_strlen(trim( $value )) > 0 ) {
                return true;
            } else {
                return false;
            }
        } elseif ( in_array( $name, [ 'autostart', 'background', 'userinterface' ] ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function makeParamString( $params ) {
        return ''; // width/height don't matter to model
    }

    /**
     * @param string $str
     * @return array|bool
     */
    public function parseParamString( $str ) {
        return false; // nothing too parse, see makeParamString above
    }

    /**
     * @param \File $image
     * @param array &$params
     * @return bool
     */
    public function normaliseParams( $image, &$params ) {
        global $wgSVGMaxSize;

        if ( !isset( $params['width'] ) ) {
            return false;
        }
        if ( !isset( $params['height'] ) || $params['height'] == -1 ) {
            $params['height'] =   $params['width']; 
        }

        // Don't make model viewer bigger than wgMaxSVGSize on the smaller side
        if ( $params['width'] <= $params['height'] ) {
            if ( $params['width'] > $wgSVGMaxSize ) {
                $params['width'] = $wgSVGMaxSize;
                $params['height'] = File::scaleHeight( $params['width'], $params['height'], $wgSVGMaxSize );
            }
        } else {
            if ( $params['height'] > $wgSVGMaxSize ) {
                $params['height'] = $wgSVGMaxSize;
                $params['width'] = File::scaleHeight( $params['height'], $params['width'], $wgSVGMaxSize );
            }
        }

        $params['autostart'] = isset( $params['autostart'] );
        global $wgGloopFilesViewerConfig;
        if ( $wgGloopFilesViewerConfig['overridebg'] === true ) {
            $params['background'] = isset( $params['background'] );
        }
        if ( $wgGloopFilesViewerConfig['overrideui'] === true ) {
            $params['userinterface'] = isset( $params['userinterface'] );
        }

        return true;
    }

    /**
     * @param \File $file
     * @param string $path Unused
     * @param bool|array $metadata
     * @return array|false
     */
    public function getImageSize( $file = null, $path = '', $metadata = false ) {
        global $wgGloopFilesConfig;
        $size = [ 500, 500 ];

        if ( (int) $wgGloopFilesConfig["width"] AND (int) $wgGloopFilesConfig["width"] > 0 ) {
            $size[0] = (int) $wgGloopFilesConfig["width"];
        }
        if ( (int) $wgGloopFilesConfig["height"] AND (int) $wgGloopFilesConfig["height"] > 0 ) {
            $size[1] = (int) $wgGloopFilesConfig["height"];
        }

        return $size;
    }

    /**
     * @param MediaHandlerState $state
     * @param string $filename
     * @return array
     */
    public function getSizeAndMetadata( $state, $filename ) {
        /**
        global $wgGloopFilesConfig;
        $width = 500;
        $height = 500;

        if ( (int) $wgGloopFilesConfig["width"] AND (int) $wgGloopFilesConfig["width"] > 0 ) {
            $width = (int) $wgGloopFilesConfig["width"];
        }
        if ( (int) $wgGloopFilesConfig["height"] AND (int) $wgGloopFilesConfig["height"] > 0 ) {
            $height = (int) $wgGloopFilesConfig["height"];
        }
         */
        $size = $this->getImageSize();

        return [
            //'width' => $width,
            'width' => $size[0],
            //'height' => $height,
            'height' => $size[1],
            'metadata' => [
                //'width' => $width,
                'width' => $size[0],
                //'height' => $height,
                'height' => $size[1],
                'version' => self::MVIEW_METADATA_VERSION
            ]
        ];
    }

    /**
     * @param MediaHandlerState $state
     * @param string $filename
     * @return array
     */
    public function getMetadata( $image, $path ) {
        $size = $this->getImageSize();

        return serialize( [
            'width' => $size[0],
            'height' => $size[1],
            'version' => self::MVIEW_METADATA_VERSION
        ] );
    }

    /**
     * Get a string describing the type of metadata, for display purposes.
     *
     * @param File $image
     * @return string
     */
    function getMetadataType( $image ) {
        return false;
    }

    /**
     * Check if the metadata string is valid for this handler.
     *
     * @deprecated since 1.37 use isFileMetadataValid
     * @param File $image
     * @param string $metadata The metadata in serialized form
     * @return bool|int
     */
    public function isMetadataValid( $image, $metadata ) {
        return $this->isFileMetadataValid( $image );
    }
    /**
     * @param File $image
     * @return bool|int
     */
    public function isFileMetadataValid( $image ) {
        $meta = $image->getMetadataArray();
        if ( isset( $meta['version'] ) && $meta['version'] == self::MVIEW_METADATA_VERSION ) {
            return self::METADATA_GOOD;
        }
        return self::METADATA_BAD;
    }

    /**
     * Get a MediaTransformOutput object representing the transformed output. Does the
     * transform unless $flags contains self::TRANSFORM_LATER.
     *
     * @param File $file
     * @param string $dstPath Filesystem destination path
     * @param string $dstUrl Destination URL to use in output HTML
     * @param array $params Arbitrary set of parameters validated by $this->validateParam()
     *   Note: These parameters have *not* gone through $this->normaliseParams()
     * @param int $flags A bitfield, may contain self::TRANSFORM_LATER
     * @return MarmosetOutputRenderer
     */
    public function doTransform( $file, $dstPath, $dstUrl, $params, $flags = 0 ) {
        $this->normaliseParams($file, $params);
        return new MarmosetOutputRenderer( $file, $params );
    }

    /**
     * Get the thumbnail extension and MIME type for a given source MIME type
     *
     * @param string $ext Extension of original file
     * @param string $mime MIME type of original file
     * @param array|null $params Handler specific rendering parameters
     * @return array Thumbnail extension and MIME type
     */
    public function getThumbType( $ext, $mime, $params = null ) {
        return [ 'mview', 'application/vnd.marmoset' ];
    }

    /**
     * @param File $file
     * @return bool True if the handled types can be transformed.
     */
    public function canRender( $file ) {
        return true;
    }

    /**
     * @param File $file
     * @return bool True if handled types cannot be displayed directly in a browser but can be rendered.
     */
    public function mustRender( $file ) {
        return true;
    }

    /**
     * @param File $file
     * @return bool The material is vectorized and thus scaling is lossless.
     */
    public function isVectorized( $file ) {
        return true;
    }

    /**
     * @param File $file
     * @return bool The material is an image, and is animated.
     */
    public function isAnimatedImage( $file ) {
        return true;
    }

    /**
     * @param File $file
     * @return bool If material is not animated, handler may return any value.
     */
    public function canAnimateThumbnail( $file ) {
        return true;
    }

    /**
     * @return bool False if the handler is disabled for all files.
     */
    public function isEnabled() {
        return true;
    }

    /**
     * @param File $file
     * @return bool|string The text of the document or false if unsupported.
     */
    public function getEntireText( File $file ) {
        return false;
    }

    /**
     * TODO: Metadata stuff.
     * Use Jpeg -> first part of file (which is thumb) appears to use jpeg type metadata/format
     * Maybe also use dimensions of thumb for width/height instead of dummy size?
     * 
     * @see https://doc.wikimedia.org/mediawiki-core/master/php/JpegHandler_8php_source.html#l00103
     * @see https://doc.wikimedia.org/mediawiki-core/master/php/BitmapMetadataHandler_8php_source.html#l00163
     *
     * Meta data valid + formatting
     * @see https://doc.wikimedia.org/mediawiki-core/master/php/ExifBitmapHandler_8php_source.html#l00085
     * @see https://doc.wikimedia.org/mediawiki-core/master/php/ImageHandler_8php_source.html#l00240
     */
    
    /**
     * Short description. Shown on Special:Search results.
     *
     * @access public
     * @param  \File $file
     * @return string
     */
    public function getShortDesc($file) {
        global $wgLang;
        $size = $this->getImageSize();
        return wfMessage('gloopfiles-mview-short-desc', $wgLang->formatSize($file->getSize()), $size[0], $size[1])->text();
    }

    /**
     * Long description. Shown under image on image description page surounded by ().
     *
     * @access public
     * @param  \File $file
     * @return string
     */
    public function getLongDesc($file) {
        global $wgLang;
        $size = $this->getImageSize();
        return wfMessage( 'gloopfiles-mview-long-desc' )->numParams( $size[0], $size[1] )->sizeParams( $file->getSize() )
            ->params( '<span class="mime-type">' . $file->getMimeType() . '</span>' )->parse();
    }

    /**
     * Shown in file history box on image description page.
     *
     * @param File $file
     * @return string Dimensions
     */
    public function getDimensionsString( $file ) {
        $size = $this->getImageSize();
        return wfMessage( 'widthheight' )
            ->numParams( $size[0], $size[1] )->text();
    }

    /**
     * Modify the parser object post-transform.
     *
     * This is often used to do $parser->addOutputHook(),
     * in order to add some javascript to render a viewer.
     * See TimedMediaHandler or OggHandler for an example.
     *
     * @param Parser $parser
     * @param File $file
     */
    public function parserTransformHook( $parser, $file ) {
        $parserOutput = $parser->getOutput();
        if ( $parserOutput->getExtensionData( 'mw_ext_GF_hasMarmoset' ) ) {
            return;
        }

        $parserOutput->addModules( ['ext.gloopfiles.mviewer'] );
        $parserOutput->setExtensionData( 'mw_ext_GF_hasMarmoset', true );
    }
}