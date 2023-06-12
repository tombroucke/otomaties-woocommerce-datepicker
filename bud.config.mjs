// @ts-check

/**
 * Build configuration
 *
 * @see {@link https://bud.js.org/guides/getting-started/configure}
 * @param {import('@roots/bud').Bud} app
 */
export default async (app) => {
  app
	  .setPath('@src', 'resources/assets')
    /**
     * Application entrypoints
     */
    .entry({
	      'otomaties-woocommerce-datepicker': ['scripts/app', 'styles/app'],
    })

    /**
     * Enable sourcemaps
     */
    .when(app.isDevelopment, (app) => app.devtool())

    /**
     * Directory contents to be included in the compilation
     */
    .assets(['images'])

    /**
     * Matched files trigger a page reload when modified
     */
    .watch(['resources/views/**/*', 'app/**/*'])

    .setPath({'@certs' : '/Users/tombroucke/Library/ApplicationSupport/Local/run/router/nginx/certs'})
    .proxy("https://atelierrosa.local")
    .serve({
          host: "atelierrosa.local",
          ssl: true,
          cert: app.path('@certs/atelierrosa.local.crt'),
          key: app.path('@certs/atelierrosa.local.key'),
          port: 3000,
    })

    /**
     * URI of the `public` directory
     */
    .setPublicPath('/app/plugins/otomaties-woocommerce-datepicker/public/');
};
