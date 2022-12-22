( function (mw, $) {

	/**
	 * @class mw.gloopfiles_mview
	 * @singleton
	 */
	mw.gloopfiles_mview = {
		initialized: false,
		instances: [],
		autostart: [],
		loaded: [],
		extconfig: {},

		/**
		 * Checks for new scenes and initialises them
		 * @return {null}
		 */
		addNew: function () {
			$('div.gf-mview-frame').each( mw.gloopfiles_mview.initViewer );
		},

		toggleBG: function () {
			marmoset.transparentBackground = !marmoset.transparentBackground;
		},

		toggleUI: function () {
			marmoset.noUserInterface = !marmoset.noUserInterface;
		},

		// TODO: Add a resize function

		/**
		 * Checks that the number of loaded scenes is less that the max allowed. If not
		 * unloads oldest scenes till max is met.
		 * @return {null}
		 */
		checkLoaded: function () {
			const self = mw.gloopfiles_mview;
			if (self.loaded.length <= self.extconfig.activelimit) { return; }
			while (self.loaded.length > self.extconfig.activelimit) {
				let i = self.loaded.shift();
				self.instances[i].viewer.unload();
			}
		},

		/**
		 * Initialises a single viewer instance
		 * @param  {integer} i Position in array of instances to add/check
		 * @param  {element} e Dom Element to initialise a viewer for
		 * @return {null}
		 */
		initViewer: function (i,e) {
			const	self = mw.gloopfiles_mview,
					frame = $(e),
					oldbg = marmoset.transparentBackground,
					oldui = marmoset.noUserInterface;
			if (frame.find('canvas').length > 0) {
				return;
			}

			let	width = frame.width() || 300,
				height = frame.height() || 300,
				file = frame.attr('src');

			if (!file) { return; }
			if ( file.search( /\.mview($|\?[a-z0-9]{5}$)/i ) < 1 ) {
				mw.log.warn('GloopFiles: Invalid file extension for marmoset file.');
				frame.addClass('gf-frame-error').html( mw.message( 'gloopfiles-mview-invalid' ).parse() );
				//frame.addClass('gf-frame-error').html( mw.message( 'gloopfiles-mview-invalid' ).parseDom() );
				return;
			}

			if ( frame.attr('data-background') && frame.attr('data-background') == 1 ) {
				marmoset.transparentBackground = !marmoset.transparentBackground;
			}
			if ( frame.attr('data-userinterface') && frame.attr('data-userinterface') == 1 ) {
				marmoset.noUserInterface = !marmoset.noUserInterface;
			}


			const viewer = new marmoset.WebViewer(width, height, file);
			frame.empty();
			// The dummy image element is a work around for packed type galleries so they only look slightly broken
			frame.append( `<img width="${width}" height="${height}" src="${file}" style="width:0; height:0; display:none;">`, viewer.domRoot);

			i = self.instances.push ({
				frame: frame,
				viewer: viewer
			});
			i -= 1;
			self.instances[i].viewer.onLoad = function () {
				self.loaded.push(i);
				mw.hook('gloopfiles.sceneloaded').fire(self.instances[i]);
				self.checkLoaded();
			};

			if (!self.initialized && frame.attr('data-autostart') && frame.attr('data-autostart') == 1) {
				self.autostart.push(i);
			}

			// Currently because of how marmoset viewer functions these can not be toggled per instance
			//marmoset.transparentBackground = oldbg;
			//marmoset.noUserInterface = oldui;
		},

		/**
		 * Initialises marmoset and the scenes and places them in the dom.
		 * @return {null}
		 */
		init: function () {
			const self = mw.gloopfiles_mview;
			self.extconfig = mw.config.get('wgGloopFilesViewerConfig');

			marmoset.dataLocale = `${mw.config.get('wgServer')}${mw.config.get('wgExtensionAssetsPath')}/GloopFiles/resources/external/marmoset/`;

			if (self.extconfig.transparent) { marmoset.transparentBackground = true; }
			if (self.extconfig.userinterface === false) { marmoset.noUserInterface = true; }

			$('div.gf-mview-frame').each( mw.gloopfiles_mview.initViewer );

			mw.hook('gloopfiles.ready').fire(self);
			self.initialized = true;

			if (self.autostart.length <= self.extconfig.autolimit) {
				self.autostart.forEach((a) => {
					self.instances[a].viewer.loadScene();
				});
			}
		}
	};

	$(function () {
		mw.gloopfiles_mview.init();
		// TODO: add some type of listener for packed galleries as they are resized dynamically, but provide no hooks/events
	})

}(mediaWiki, jQuery) );
