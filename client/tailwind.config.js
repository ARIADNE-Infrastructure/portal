/**
 * instead of tailwinds bg-opacity, border etc (which doesn't work in internet explorer)
 * this returns all colors with variations like yellow-10, yellow-50 etc (0-100)
 * look at /theme page for full list
 *
 * no es6 syntax here - crashes internet explorer (doesn't get transformed)
 * */
function getColorsWithOpacity (colors) {
  for (var key in colors) {
    var hex = colors[key];

    for (var i = 0; i <= 10; i++) {
      hex = hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, function (m, r, g, b) {
        return r + r + g + g + b + b;
      });
      var rgb = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      colors[key + '-' + (i * 10)] = 'rgba(' +
        parseInt(rgb[1], 16) + ',' +
        parseInt(rgb[2], 16) + ',' +
        parseInt(rgb[3], 16) + ',' +
        (i / 10) + ')';
    }
  }
  return colors;
}

module.exports = {
  /**
   * Css purge is enabled
   * Read this: https://tailwindcss.com/docs/controlling-file-size/
   *
   * Minifies style.css from 1mb to ~30kb
   * Causes some problems when adding new styles, or when trying to use already purged - to fix restart dev server
   * Could perhaps only have this enabled for "production" - but easy to miss if things gets purged
   */
  purge: {
    enabled: true,
    content: [
      './src/**/*.html',
      './src/**/*.vue',
      './src/**/*.ts',
    ]
  },
  variants: {
    borderWidth: ['responsive', 'last', 'hover', 'focus'],
  },
  theme: {
    fontFamily: {
      'base': '"Roboto", Helvetica, sans-serif',
    },

    fontSize: {
      'xs':   '.5rem',
      'sm':   '.75rem',
      'md':   '.85rem',
      'mmd':  '.9rem',
      'base': '14px',
      'lg':   '1.25rem',
      'xl':   '1.5rem',
      '2xl':  '1.75rem',
      '2x':   '2rem',
      '3x':   '3rem',
      '4x':   '4rem',
      '5x':   '5rem',
    },

    colors: getColorsWithOpacity({
      // primary
      yellow: '#D5A03A',
      red:    '#BB3921',
      green:  '#75A99D',
      blue:   '#135C77',

      // general
      darkGray:    '#333',
      midGray2:    '#555',
      midGray:     '#777',
      gray:        '#ccc',
      lightGray:   '#efefef',
      black:       '#000',
      white:       '#fff',
      lightYellow: '#ffffcc',
    }),

    placeholderColor: getColorsWithOpacity({
      black:     '#000',
    }),

    screens: {
      'xs': '320px',
      'sm': '568px',
      'md': '768px',
      'lg': '1000px',
      'xl': '1440px',
    },

    // padding, margin, width, and height
    spacing: {
      'none': '0',
      '1':    '1px',
      'xs':   '.25rem',
      'sm':   '.5rem',
      'md':   '.75rem',
      'base': '1rem',
      'lg':   '1.25rem',
      'xl':   '1.5rem',
      '2xl':  '1.75rem',
      '2x':   '2rem',
      '3x':   '3rem',
      '4x':   '4rem',
      '5x':   '5rem',
      '6x':   '6rem',
      '7x':   '7rem',
      '8x':   '8rem',
      '9x':   '9rem',
      '10x':  '10rem',
    },

    borderWidth: {
      '0':    '0',
      'base': '1px',
      '2':    '2px',
      '3':    '3px',
      '4':    '4px',
      '6':    '6px',
      '8':    '8px',
    },

    borderRadius: {
      '0':    '0',
      'base': '4px',
      'lg':   '10px',
      'full': '9999px',
    },

    opacity: {
      '0':   '0',
      '10':  '.1',
      '20':  '.2',
      '30':  '.3',
      '40':  '.4',
      '50':  '.5',
      '60':  '.6',
      '70':  '.7',
      '80':  '.8',
      '90':  '.9',
      '100': '1',
    },

    // top, right, bottom, left, and inset
    inset: {
      '0':    '0',
      'auto': 'auto',
      'xs':   '.25rem',
      'sm':   '.5rem',
      'md':   '.75rem',
      'base': '1rem',
      'lg':   '1.25rem',
      'xl':   '1.5rem',
      '2xl':  '1.75rem',
      '3xl':  '2rem',
      '4xl':  '3rem',
      '5xl':  '4rem',
      '-base': '-1rem',
      'full': '100%',
    },

    zIndex: {
      'neg':  -1,
      '0':    0,
      '1':    1,
      '2':    2,
      '3':    3,
      '4':    4,
      '1001': 1001,
      '1002': 1002,
      '1003': 1003,
    },

    boxShadow: {
      'none': 'none',
      'bottom': '0 4px 2px -2px #ddd',
    },

    minWidth: {
      '0': '0',
      '1/4': '25%',
      '1/2': '50%',
      '3/4': '75%',
      'full': '100%',
    }
  }
}
