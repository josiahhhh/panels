const colors = require('tailwindcss/colors');

const gray = {
    50: 'hsl(216, 33%, 97%)',
    100: 'hsl(214, 15%, 91%)',
    200: 'hsl(210, 16%, 82%)',
    300: 'hsl(211, 13%, 65%)',
    400: 'hsl(211, 10%, 53%)',
    500: 'hsl(211, 12%, 43%)',
    600: 'hsl(209, 14%, 37%)',
    700: 'hsl(209, 18%, 30%)',
    800: 'hsl(209, 20%, 25%)',
    900: 'hsl(210, 24%, 16%)',
};

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        fontFamily: {
            sans: [ 'Rubik', '-apple-system', 'BlinkMacSystemFont', '"Helvetica Neue"', '"Roboto"', 'system-ui', 'sans-serif' ],
            header: [ '"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif' ],
            mono: [ '"IBM Plex Mono"', '"Source Code Pro"', 'SourceCodePro', 'Menlo', 'Monaco', 'Consolas', 'monospace' ],
        },
        colors: {
            transparent: 'transparent',
            black: 'hsl(210, 27%, 10%)',
            white: '#ffffff',
            blue: colors.blue,
            primary: {
                50: 'hsl(202, 100%, 95%)', // lightest
                100: 'hsl(204, 100%, 86%)', // lighter
                200: 'hsl(206, 93%, 73%)',
                300: 'hsl(208, 88%, 62%)',
                400: 'hsl(210, 83%, 53%)', // light
                500: 'hsl(212, 92%, 43%)', // base
                600: 'hsl(214, 95%, 36%)', // dark
                700: 'hsl(215, 96%, 32%)',
                800: 'hsl(216, 98%, 25%)', // darker
                900: 'hsl(218, 100%, 17%)', // darkest
            },
            neutral: {
                50: 'hsl(216, 33%, 97%)',
                100: 'hsl(214, 15%, 91%)',
                200: 'hsl(210, 16%, 82%)',
                300: 'hsl(211, 13%, 65%)',
                400: 'hsl(211, 10%, 53%)',
                500: 'hsl(211, 12%, 43%)',
                600: 'hsl(209, 14%, 37%)',
                700: 'hsl(209, 18%, 30%)',
                800: 'hsl(209, 20%, 25%)',
                900: 'hsl(210, 24%, 16%)',
            },
            red: {
                50: '#FFF2F5',
                100: '#FEE6EA',
                200: '#FDBFCC',
                300: '#FC99AD',
                400: '#FA4D6F',
                500: '#F80031',
                600: '#DF002C',
                700: '#95001D',
                800: '#700016',
                900: '#4A000F',
            },
            yellow: {
                50: '#FFF5F3',
                100: '#FFEBE6',
                200: '#FFCDC2',
                300: '#FFAF9D',
                400: '#FF7353',
                500: '#FF3709',
                600: '#E63208',
                700: '#992105',
                800: '#731904',
                900: '#4D1103',
            },
            cyan: {
                50: 'hsl(171, 82%, 94%)',
                100: 'hsl(172, 97%, 88%)',
                200: 'hsl(174, 96%, 78%)',
                300: 'hsl(176, 87%, 67%)',
                400: 'hsl(178, 78%, 57%)',
                500: 'hsl(180, 77%, 47%)',
                600: 'hsl(182, 85%, 39%)',
                700: 'hsl(184, 90%, 34%)',
                800: 'hsl(186, 91%, 29%)',
                900: 'hsl(188, 91%, 23%)',
            },
            green: {
                50: '#F4FBF8',
                100: '#E8F7F1',
                200: '#C6EBDB',
                300: '#A3DFC5',
                400: '#5FC69A',
                500: '#1AAE6F',
                600: '#179D64',
                700: '#106843',
                800: '#0C4E32',
                900: '#083421',
            },
            iceline: {
                25: 'hsl(214, 83%, 50%)',
                50: 'hsl(233, 38%, 9%)',
                100: 'hsl(244, 21%, 15%)',
                150: 'hsl(240, 20%, 12%)',
                200: 'hsl(231, 39%, 13%)',
                250: 'hsl(235, 35%, 6%)',
                300: 'hsl(218, 54%, 20%)',
            },
            icelinemainbackground: {
                50: '#F3F3F4',
                100: '#E7E7E9',
                200: '#C3C3C7',
                300: '#9F9FA5',
                400: '#565862',
                500: '#0E101F',
                600: '#0D0E1C',
                700: '#080A13',
                800: '#06070E',
                900: '#040509',
            },
            icelinenavbar: {
                50: '#F4F4F5',
                100: '#E9E9EA',
                200: '#C7C7CB',
                300: '#A5A5AB',
                400: '#62626D',
                500: '#1F1E2E',
                600: '#1C1B29',
                700: '#13121C',
                800: '#0E0E15',
                900: '#09090E',
            },
            icelinebox: {
                50: '#F3F3F5',
                100: '#E8E8EA',
                200: '#C4C5CB',
                300: '#A1A3AB',
                400: '#5B5D6D',
                500: '#14182E',
                600: '#121629',
                700: '#0C0E1C',
                800: '#090B15',
                900: '#06070E',
            },
            icelinebrandcolour: {
                50: '#F3F8FE',
                100: '#E8F1FD',
                200: '#C5DCF9',
                300: '#A1C7F6',
                400: '#5B9CEF',
                500: '#1572E8',
                600: '#1367D1',
                700: '#0D448B',
                800: '#093368',
                900: '#062246',
            },
            icelineiconbackground: {
                50: '#F3F3F4',
                100: '#E7E7E8',
                200: '#C2C2C6',
                300: '#9D9EA3',
                400: '#54555E',
                500: '#0B0C19',
                600: '#0A0B17',
                700: '#07070F',
                800: '#05050B',
                900: '#030408',
            },

            /* Colors for custom Iceline theme. */

            icelinePrimary: '#2986DD',
            icelineSidebarSelected: '#101224',
        },
        extend: {
            fontFamily: {
                header: ['"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif'],
            },
            colors: {
                black: '#131a20',
                // "primary" and "neutral" are deprecated, prefer the use of "blue" and "gray"
                // in new code.
                primary: colors.blue,
                gray: gray,
                neutral: gray,
                cyan: colors.cyan,
            },
            fontSize: {
                '2xs': '0.625rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            minWidth: {
                48: '12rem',
            },
            width: {
                sidebar: '7.5rem',
            },
            gridTemplateColumns: {
                dashboard: 'auto 1fr',
                serverMetric: '4rem 1fr',
            },
            borderColor: theme => ({
                default: theme('colors.neutral.400', 'currentColor'),
            }),
        },
    },
    plugins: [
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
    ]
};
