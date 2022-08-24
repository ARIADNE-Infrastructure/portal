/**
 * Chartjs plugin dragzone
 *
 * A bit modified - original only has a callback on dragend, this also calls back on mousemove
 *
 * https://www.npmjs.com/package/chartjs-plugin-dragzone
 * https://github.com/leey0818/chartjs-plugin-dragzone/blob/master/src/index.js
 */

import Chart from 'chart.js';

const { _isPointInArea } = Chart.canvasHelpers;

function getXAxis(chartInstance) {
  var scales = chartInstance.scales;
  var scaleIds = Object.keys(scales);
  for (var i = 0; i < scaleIds.length; i++) {
    var scale = scales[scaleIds[i]];

    if (scale.isHorizontal()) {
      return scale;
    }
  }
}

function getYAxis(chartInstance) {
  var scales = chartInstance.scales;
  var scaleIds = Object.keys(scales);
  for (var i = 0; i < scaleIds.length; i++) {
    var scale = scales[scaleIds[i]];

    if (!scale.isHorizontal()) {
      return scale;
    }
  }
}

function isAllowVertical(direction) {
  return direction === 'all' || direction === 'vertical';
}

function isAllowHorizontal(direction) {
  return direction === 'all' || direction === 'horizontal';
}

/**
* Get drag area
* @param {Chart} chartInstance chart instance
* @param {MouseEvent} beginPoint begin event
* @param {MouseEvent} endPoint end event
*/
function getDragArea(chartInstance, beginPoint, endPoint) {
  const props = chartInstance.$dragzone;
  const options = props._options;

  const xAxis = getXAxis(chartInstance);
  const yAxis = getYAxis(chartInstance);

  let startX = xAxis.left;
  let endX = xAxis.right;
  let startY = yAxis.top;
  let endY = yAxis.bottom;

  if (isAllowHorizontal(options.direction)) {
    const offsetX = beginPoint.target.getBoundingClientRect().left;
    startX = Math.min(beginPoint.clientX, endPoint.clientX) - offsetX;
    endX = Math.max(beginPoint.clientX, endPoint.clientX) - offsetX;
  }

  if (isAllowVertical(options.direction)) {
    const offsetY = beginPoint.target.getBoundingClientRect().top;
    startY = Math.min(beginPoint.clientY, endPoint.clientY) - offsetY;
    endY = Math.max(beginPoint.clientY, endPoint.clientY) - offsetY;
  }

  return {
    startX, startY,
    endX, endY,
  };
};

/**
* Get data for point
* @param {Chart} chart Chart chartInstance
* @param {ChartElement} point Chart point data
*/
function getPointData(chart, point) {
  const datasetIndex = point._datasetIndex;
  const dataIndex = point._index;

  return chart.data.datasets[datasetIndex].data[dataIndex];
}

function isClickArea(direction, startX, endX, startY, endY) {
  if (direction === 'all') return startX === endX && startY === endY;
  if (direction === 'vertical') return startY === endY;
  if (direction === 'horizontal') return startX === endX;
  return false;
}

// Set plugin default global options
Chart.defaults.global.plugins.dragzone = {
  direction: 'all',
  color: '#4692ca4d',
};

const dragZonePlugin = {
  id: 'dragzone',

  beforeInit(chartInstance) {
    chartInstance.$dragzone = {};

    const options = chartInstance.options.plugins.dragzone;
    const props = chartInstance.$dragzone;
    const node = chartInstance.ctx.canvas;
    props._node = node;
    props._options = options;

    props._mouseDownHandler = function (evt) {
      node.addEventListener('mousemove', props._mouseMoveHandler);
      if (!options.keys || options.keys.some(key => evt[key])) {
        if (typeof options.onDragStart === 'function') {
          options.onDragStart();
        }
        props._dragZoneStart = evt;
      }
    };

    let timeout = 0
    props._mouseMoveHandler = function (evt) {
      if (props._dragZoneStart) {
        props._dragZoneEnd = evt;

        // throttle update every 100ms
        calcFromTo(evt, false);
        cancelAnimationFrame(timeout)
        timeout = requestAnimationFrame(() => chartInstance.update('active'));
      }
    };

    props._mouseUpHandler = function (evt) {
      if (!props._dragZoneStart) {
        return;
      }

      node.removeEventListener('mousemove', props._mouseMoveHandler);

      calcFromTo(evt, true);

      props._dragZoneStart = null;
      props._dragZoneEnd = null;
    };

    const calcFromTo = function (evt, done) {
      const beginPoint = props._dragZoneStart;
      const { startX, startY, endX, endY } = getDragArea(chartInstance, beginPoint, evt);

      // ignore click only event
      if (isClickArea(options.direction, startX, endX, startY, endY)) return;

      const area = {
        left: startX,
        right: endX,
        top: startY,
        bottom: endY,
      };

      const datasets = chartInstance.data.datasets;
      let first
      let last

      for (let i = 0; i < datasets.length; i++) {
        const data = chartInstance.getDatasetMeta(i).data;

        for (let j = 0; j < data.length; j++) {
          const point = data[j];

          // check point in drag area
          if (_isPointInArea(point._model, area)) {
            if (first === undefined) {
              first = j
            }
            last = j
          }
        }
      }
      if (typeof options.onDragSelection === 'function') {
        options.onDragSelection(first, last, done);
      }
    };

    // add drag event listener
    node.addEventListener('mousedown', props._mouseDownHandler);
    node.ownerDocument.addEventListener('mouseup', props._mouseUpHandler);
  },

  beforeDatasetsDraw(chartInstance) {
    const props = chartInstance.$dragzone;

    if (props._dragZoneEnd) {
      const ctx = chartInstance.ctx;
      const options = props._options;
      const beginPoint = props._dragZoneStart;
      const endPoint = props._dragZoneEnd;
      const { startX, startY, endX, endY } = getDragArea(chartInstance, beginPoint, endPoint);

      const rectWidth = endX - startX;
      const rectHeight = endY - startY;

      ctx.save();
      ctx.beginPath();
      ctx.fillStyle = options.color;
      ctx.fillRect(startX, startY, rectWidth, rectHeight);

      ctx.restore();
    }
  },

  destroy(chartInstance) {
    if (!chartInstance.$dragzone) return;

    const props = chartInstance.$dragzone;
    const node = props._node;

    node.removeEventListener('mousedown', props._mouseDownHandler);
    node.removeEventListener('mousemove', props._mouseMoveHandler);
    node.ownerDocument.removeEventListener('mouseup', props._mouseUpHandler);

    delete chartInstance.$dragzone;
  }
};

// Register chart.js plugin
Chart.plugins.register(dragZonePlugin);

export default dragZonePlugin;
