
			// Replace the <textarea id="editor"> with an CKEditor
			// instance, using the "bbcode" plugin, shaping some of the
			// editor configuration to fit BBCode environment.
			CKEDITOR.replace( 'editor1',
				{
					//extraPlugins : 'bbcode',
					// Remove unused plugins.
					removePlugins : 'bidi,button,dialogadvtab,div,flash,forms,iframe,indent,liststyle,pagebreak,showborders,table,tabletools,templates',
					// Width and height are not supported in the BBCode format, so object resizing is disabled.
					disableObjectResizing : true,
					// Define font sizes in percent values.
					fontSize_sizes : "75/75%;100/100%;120/120%;150/150%;200/200%",
					toolbar :
					[
						['Source','Undo','Redo'],
						['Styles', 'Format'],
						['Link', 'Unlink', 'Image', 'Smiley','SpecialChar'],
						['Find','Replace','-','SelectAll','RemoveFormat'],
						['Preview', 'Print','-','Maximize'],
						
						'/',
						['Bold', 'Italic','Underline'],
						['Cut','Copy','Paste','PasteText','PasteFromWord'],
						['JustifyLeft','JustifyCenter','JustifyRight'],
						['FontSize','Font'],
						['TextColor','BGColor'],
						['NumberedList','BulletedList','-','Blockquote'],
						
					],
					 width: "800px",
                     height: "320px",
					// Strip CKEditor smileys to those commonly used in BBCode.
					smiley_images :
					[
						'regular_smile.gif','sad_smile.gif','wink_smile.gif','teeth_smile.gif','tounge_smile.gif',
						'embaressed_smile.gif','omg_smile.gif','whatchutalkingabout_smile.gif','angel_smile.gif','shades_smile.gif',
						'cry_smile.gif','kiss.gif'
					],
					smiley_descriptions :
					[
						'smiley', 'sad', 'wink', 'laugh', 'cheeky', 'blush', 'surprise',
						'indecision', 'angel', 'cool', 'crying', 'kiss'
					]
			} );
			
			
			
			
			CKEDITOR.replace( 'editor2',
				{
					//extraPlugins : 'bbcode',
					// Remove unused plugins.
					removePlugins : 'bidi,button,dialogadvtab,div,flash,forms,iframe,indent,liststyle,pagebreak,showborders,table,tabletools,templates',
					// Width and height are not supported in the BBCode format, so object resizing is disabled.
					disableObjectResizing : true,
					// Define font sizes in percent values.
					fontSize_sizes : "75/75%;100/100%;120/120%;150/150%;200/200%",
					toolbar :
					[
						['Source','Undo','Redo'],
						['Styles', 'Format'],
						['Link', 'Unlink', 'Image', 'Smiley','SpecialChar'],
						['Find','Replace','-','SelectAll','RemoveFormat'],
						['Preview', 'Print','-','Maximize'],
						
						'/',
						['Bold', 'Italic','Underline'],
						['Cut','Copy','Paste','PasteText','PasteFromWord'],
						['JustifyLeft','JustifyCenter','JustifyRight'],
						['FontSize','Font'],
						['TextColor','BGColor'],
						['NumberedList','BulletedList','-','Blockquote'],
						
					],
					 width: "800px",
                     height: "110px",
					// Strip CKEditor smileys to those commonly used in BBCode.
					smiley_images :
					[
						'regular_smile.gif','sad_smile.gif','wink_smile.gif','teeth_smile.gif','tounge_smile.gif',
						'embaressed_smile.gif','omg_smile.gif','whatchutalkingabout_smile.gif','angel_smile.gif','shades_smile.gif',
						'cry_smile.gif','kiss.gif'
					],
					smiley_descriptions :
					[
						'smiley', 'sad', 'wink', 'laugh', 'cheeky', 'blush', 'surprise',
						'indecision', 'angel', 'cool', 'crying', 'kiss'
					]
			} );
			
			CKEDITOR.replace( 'editor3',
				{
					//extraPlugins : 'bbcode',
					// Remove unused plugins.
					removePlugins : 'bidi,button,dialogadvtab,div,flash,forms,iframe,indent,liststyle,pagebreak,showborders,table,tabletools,templates',
					// Width and height are not supported in the BBCode format, so object resizing is disabled.
					disableObjectResizing : true,
					// Define font sizes in percent values.
					fontSize_sizes : "75/75%;100/100%;120/120%;150/150%;200/200%",
					toolbar :
					[
						['Source','Undo','Redo'],
						['Styles', 'Format'],
						['Link', 'Unlink', 'Image', 'Smiley','SpecialChar'],
						['Find','Replace','-','SelectAll','RemoveFormat'],
						['Preview', 'Print','-','Maximize'],
						
						'/',
						['Bold', 'Italic','Underline'],
						['Cut','Copy','Paste','PasteText','PasteFromWord'],
						['JustifyLeft','JustifyCenter','JustifyRight'],
						['FontSize','Font'],
						['TextColor','BGColor'],
						['NumberedList','BulletedList','-','Blockquote'],
						
					],
					 width: "800px",
                     height: "90px",
					// Strip CKEditor smileys to those commonly used in BBCode.
					smiley_images :
					[
						'regular_smile.gif','sad_smile.gif','wink_smile.gif','teeth_smile.gif','tounge_smile.gif',
						'embaressed_smile.gif','omg_smile.gif','whatchutalkingabout_smile.gif','angel_smile.gif','shades_smile.gif',
						'cry_smile.gif','kiss.gif'
					],
					smiley_descriptions :
					[
						'smiley', 'sad', 'wink', 'laugh', 'cheeky', 'blush', 'surprise',
						'indecision', 'angel', 'cool', 'crying', 'kiss'
					]
			} );