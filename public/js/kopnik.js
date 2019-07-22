/**
 * @constructor
 * @extends {module:ol/interaction/Pointer}
 */
var Drag = (function (PointerInteraction) {
  function Drag() {
    ol.interaction.Pointer.call(this, {
      handleDownEvent: handleDownEvent,
      handleDragEvent: handleDragEvent,
      handleMoveEvent: handleMoveEvent,
      handleUpEvent: handleUpEvent
    });

    /**
     * @type {module:ol/pixel~Pixel}
     * @private
     */
    this.coordinate_ = null;

    this.lonlat_ = null;

    /**
     * @type {string|undefined}
     * @private
     */
    this.cursor_ = 'pointer';

    /**
     * @type {module:ol/Feature~Feature}
     * @private
     */
    this.feature_ = null;

    /**
     * @type {string|undefined}
     * @private
     */
    this.previousCursor_ = undefined;
  }

  if ( ol.interaction.Pointer ) Drag.__proto__ = ol.interaction.Pointer;
  Drag.prototype = Object.create( ol.interaction.Pointer && ol.interaction.Pointer.prototype );
  Drag.prototype.constructor = Drag;

  return Drag;
}(new ol.interaction.Pointer));

/**
 * @param {module:ol/MapBrowserEvent~MapBrowserEvent} evt Map browser event.
 * @return {boolean} `true` to start the drag sequence.
 */
function handleDownEvent(evt) {
  var map = evt.map;

  var feature = map.forEachFeatureAtPixel(evt.pixel,
    function(feature) {
      return feature;
    });

  if (feature) {
    this.coordinate_ = evt.coordinate;
    this.feature_ = feature;
  }

  return !!feature;
}

/**
 * @param {module:ol/MapBrowserEvent~MapBrowserEvent} evt Map browser event.
 */
function handleDragEvent(evt) {
  var deltaX = evt.coordinate[0] - this.coordinate_[0];
  var deltaY = evt.coordinate[1] - this.coordinate_[1];

  var geometry = this.feature_.getGeometry();
  geometry.translate(deltaX, deltaY);

  this.coordinate_[0] = evt.coordinate[0];
  this.coordinate_[1] = evt.coordinate[1];

  var coord = this.feature_.getGeometry().getCoordinates();

  this.lonlat_ = ol.proj.transform(coord, 'EPSG:3857', 'EPSG:4326');
}

/**
 * @param {module:ol/MapBrowserEvent~MapBrowserEvent} evt Event.
 */
function handleMoveEvent(evt) {
  if (this.cursor_) {
    var map = evt.map;
    var feature = map.forEachFeatureAtPixel(evt.pixel,
      function(feature) {
        return feature;
      });
    var element = evt.map.getTargetElement();
    if (feature) {
      if (element.style.cursor != this.cursor_) {
        this.previousCursor_ = element.style.cursor;
        element.style.cursor = this.cursor_;
      }
    } else if (this.previousCursor_ !== undefined) {
      element.style.cursor = this.previousCursor_;
      this.previousCursor_ = undefined;
    }
  }
}

/**
 * @return {boolean} `false` to stop the drag sequence.
 */
function handleUpEvent() {
  $('#form_latitude').attr('value', this.lonlat_[1].toFixed(10));
  $('#form_longitude').attr('value', this.lonlat_[0].toFixed(10));

  this.coordinate_ = null;
  this.feature_ = null;
  return false;
}

/**
 * Отрисовка карты
 *
 * @param lon
 * @param Lat
 */
function renderDragableMap(lon, lat) {
  var iconFeature2 = new ol.Feature({
    geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat])),
    name: 'Somewhere',
  });

  // specific style for that one point
  iconFeature2.setStyle(new ol.style.Style({
    image: new ol.style.Icon({
      anchor: [0.5, 46],
      anchorXUnits: 'fraction',
      anchorYUnits: 'pixels',
      src: 'https://openlayers.org/en/v5.3.0/examples/data/icon.png'
    })
  }));

  const iconLayerSource = new ol.source.Vector({
    features: [iconFeature2],
    projection: 'EPSG:4326'
  });

  const iconLayer = new ol.layer.Vector({
    source: iconLayerSource,
    // style for all elements on a layer
    style: new ol.style.Style({
      image: new ol.style.Icon({
        anchor: [0.5, 46],
        anchorXUnits: 'fraction',
        anchorYUnits: 'pixels',
        src: 'https://openlayers.org/en/v5.3.0/examples/data/icon.png'
      })
    }),
  });

  var interactions = new ol.interaction.defaults;

  var map = new ol.Map({
    interactions: interactions.extend([new Drag()]),
    target: 'mapdiv',
    layers: [
      new ol.layer.Tile({
        source: new ol.source.OSM()
      }),
      iconLayer
    ],
    view: new ol.View({
      center: ol.proj.fromLonLat([lon, lat]),
      // center: ol.proj.fromLonLat([82.911540, 55.059946]),
      // center: ol.proj.fromLonLat([105.319, 61.524]),
      zoom: 15
    })
  });
}
