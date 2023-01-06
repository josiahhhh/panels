import React from 'react';
import ReactDOM from 'react-dom';

import * as Sentry from '@sentry/react';
import { ExtraErrorData, CaptureConsole } from '@sentry/integrations';
import { Integrations } from '@sentry/tracing';

import App from '@/components/App';
import { setConfig } from 'react-hot-loader';

// Enable language support.
import './i18n';

import { history } from '@/components/history';

// Prevents page reloads while making component changes which
// also avoids triggering constant loading indicators all over
// the place in development.
//
// @see https://github.com/gaearon/react-hot-loader#hook-support
setConfig({ reloadHooks: false });

// Initialize the Sentry SDK
Sentry.init({
    dsn: 'https://6789c3685ede4818b1197d7e14897df7@sentry.iceline.host/5',
    maxBreadcrumbs: 50,
    environment: process.env.NODE_ENV || 'development',
    // release: __COMMIT_HASH__, // passed by webpack
    autoSessionTracking: true,
    integrations: [
        new Integrations.BrowserTracing({
            routingInstrumentation: Sentry.reactRouterV5Instrumentation(history),
        }),
        new ExtraErrorData({
            depth: 5,
        }),
        new CaptureConsole({
            levels: ['error'],
        }),
    ],

    // We recommend adjusting this value in production, or using tracesSampler
    // for finer control
    tracesSampleRate: 0.5,

    normalizeDepth: 3,

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    beforeSend(event, hint) {
        // Check if it is an exception, and if so, show the report dialog
        /* if (event.exception) {
            Sentry.showReportDialog({ eventId: event.event_id });
        } */

        return event;
    },
});

ReactDOM.render(<App />, document.getElementById('app'));
