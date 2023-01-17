/**
 * Backbone templates
 */
dlmRowModelTopDownloads = Backbone.Model.extend(
	{
		initialize: function (args) {
			var model = this;

			jQuery.each(args, function (att, value) {
				model.set(att, value);
			});

			var rowView = new dlmBackBone['viewTopDownloads'](
				{
					'model'     : model,
					'el'        : jQuery('.total_downloads_table__list'),
					'childViews': dlmBackBone.topDownloadsChildViews
				}
			);
			model.set('view', rowView);
			rowView.render();
		},
	}
);

dlmRowViewTopDownloads = Backbone.View.extend(
	{
		/**
		 * Template
		 * - The template to load inside the above tagName element
		 */
		template: wp.template('dlm-top-downloads-row'),

		initialize: function (args) {
			// Child Views
			this.childViews = ('undefined' !== typeof args.childViews) ? args.childViews : [];
		},

		render: function () {
			const element    = this.$el;
			const childViews = this.childViews;
			element.append(this.template(this.model.attributes));

			// Generate Child Views
			if (childViews.length > 0) {
				childViews.forEach(function (view) {

					// Init with model
					var childView = new view(
						{
							model: this.model
						}
					);
					let childHTML = childView.render().el;
					// Render view within our main view
					element.find('.dlm-reports-table__line[data-id="' + this.model.attributes.id + '"]').append(childHTML.innerHTML);
				}, this);
			}
			return this;
		}
	}
);

dlmRowModelUserLogs = Backbone.Model.extend(
	{
		initialize: function (args) {
			var model = this;

			jQuery.each(args, function (att, value) {
				model.set(att, value);
			});

			var rowView = new dlmBackBone['viewUserLogs'](
				{
					'model': model,
					'el'   : jQuery('.user-logs__list'),
					'childViews': dlmBackBone.userLogsChildViews
				}
			);

			model.set('view', rowView);
			rowView.render();
		},
	}
);

dlmRowViewUserLogs = Backbone.View.extend(
	{
		/**
		 * Template
		 * - The template to load inside the above tagName element
		 */
		template: wp.template('dlm-user-logs-row'),

		initialize: function (args) {
			// Child Views
			this.childViews = ('undefined' !== typeof args.childViews) ? args.childViews : [];
		},

		render: function () {
			const element    = this.$el;
			const childViews = this.childViews;
			element.append(this.template(this.model.attributes));

			// Generate Child Views
			if (childViews.length > 0) {
				childViews.forEach(function (view) {

					// Init with model
					var childView = new view(
						{
							model: this.model
						}
					);
					let childHTML = childView.render().el;
					// Render view within our main view
					element.find('.dlm-reports-table__line[data-id="' + this.model.attributes.key + '"]').append(childHTML.innerHTML);
				}, this);
			}
			return this;
		}
	}
);

dlmBackBone = {
	'modelTopDownloads'     : dlmRowModelTopDownloads,
	'viewTopDownloads'      : dlmRowViewTopDownloads,
	'topDownloadsChildViews': [],
	'modelUserLogs'         : dlmRowModelUserLogs,
	'viewUserLogs'          : dlmRowViewUserLogs,
	'userLogsChildViews'    : [],
};
/**
 * End Backbone templates
 */