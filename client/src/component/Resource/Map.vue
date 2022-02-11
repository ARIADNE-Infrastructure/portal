<template>
  <div>
    <div v-show="map" class="xxx-mb-lg" :class="{ 'leaflet-wrapper--hide-similar': !showNearby }">
      <div id="map" style="height:300px"></div>

      <div class="bg-lightGray-60">
        <div class="max-w-screen-xl mx-auto block md:flex md:justify-between items-center text-center text-midGray text-sm">

          <div class="py-md px-base flex items-center">

            <i v-if="isPolygonSpatial"
              class="fa fa-draw-polygon text-red text-2xl mr-xs"
            />
            <img v-else
              :src="mapUtils.markerType.point.current"
              width="15"
              class="inline mr-xs"
              alt="current marker"
            >

            Current resource <div v-if="locationIsApproximate">&nbsp;( Resource location is approximate )</div>

            <template v-if="hasNearbyResources">
              <img
                :src="mapUtils.markerType.point.marker"
                width="15"
                class="inline ml-lg mr-xs"
                alt="similar marker"
              >
              Nearby resource
              <b-link
                class="ml-xs"
                :clickFn="() => toggleNearby()"
              >
                <b v-if="showNearby">(hide)</b><b v-else>(show)</b>
              </b-link>
            </template>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!map" class="pb-md"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { generalModule, resourceModule } from "@/store/modules";
import * as L from 'leaflet';
import 'leaflet.heat';
import 'leaflet/dist/leaflet.css';
import BLink from '../Base/Link.vue';
import utils from '@/utils/utils';
import mapUtils from '@/utils/map';

// WKT reader/helper for map
import 'wicket/wicket-leaflet';
import Wkt from 'wicket';

@Component({
  components: {
    BLink,
  }
})

export default class ResourceMap extends Vue {
  map: any = null;
  showNearby: boolean = true; // Show similar as default
  isPolygonSpatial: boolean = false;
  mapUtils = mapUtils;
  locationIsApproximate: boolean = false;
  nearbyMarkersGroup = L.featureGroup();
  resourceMarkersGroup = L.featureGroup();
  mapZoomPadding = {padding: [20,20]};

  mounted () {
    this.setupMap();
    this.setMap();
  }

  get resource () {
    return resourceModule.getResource;
  }

  get assets (): string {
    return generalModule.getAssetsDir;
  }

  get hasNearbyResources (): boolean {
    return !!this.resource?.nearby?.length;
  }

  /**
   * Toggle nearby resources
   */
  toggleNearby (): void {
    this.showNearby = !this.showNearby;
    if(this.showNearby) {
      let allLayers = this.resourceMarkersGroup.getLayers().concat(this.nearbyMarkersGroup.getLayers());
      this.map.addLayer(this.nearbyMarkersGroup);
      this.map.fitBounds(L.featureGroup(allLayers).getBounds(),this.mapZoomPadding);
    } else {
      this.map.removeLayer(this.nearbyMarkersGroup);
      this.map.fitBounds(this.resourceMarkersGroup.getBounds(),this.mapZoomPadding); 
    }
  }

  /**
   * Map body
   */
  setupMap() {

    this.map = L.map('map', {
      zoomControl: false,
      scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(this.map);
    this.map.addControl(L.control.zoom({ position: 'bottomright' }));

  }

  /**
   * Map content, markers and geoshapes
   */
  async setMap () {

    let marker: any = null;  
    this.isPolygonSpatial = false;

    // Pane allows for settings z-index. All other documented approaches fail!
    this.map.createPane( 'resourceMarkersPane' );
    this.map.getPane('resourceMarkersPane').style.zIndex = 1000;

    // Get locations from resource and create markers
    this.resource.spatial?.forEach((spatial: any, i: number) => {

      if(spatial.spatialPrecision || spatial.coordinatePrecision ) {
        // If either of these props are set, means that resource location is apporoximate!
        this.locationIsApproximate = true;
      }

      // Handle geopoint data
      if (spatial?.geopoint) {

        if (this.resource.spatial[i + 1]?.placeName && !this.resource.spatial[i + 1]?.geopoint) {
          spatial.placeName = this.resource.spatial[i + 1]?.placeName;
        }

        // Create and set marker to current map
        marker = this.getMarker(this.resource.id, spatial, mapUtils.markerType.point.current, this.resource.title.text);
        marker.addTo( this.resourceMarkersGroup );
        
      }

      // Handle polygon data and/or boundingbox
      if(spatial?.polygon || spatial?.boundingbox) {

        // incoming data can either be polygon or boundingbox. Handles the same way.
        let shape = spatial?.polygon || spatial?.boundingbox;

        // Create a new Wicket instance
        // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
        const wkt = new Wkt.Wkt();
        wkt.read(shape);
        // Add to custom pane to be able to set z-index for geoshapes
        // Interactive: false => because we must be able to show tooltips for nearby markers under the geoshape
        let feature = wkt.toObject({color: 'red', pane: 'resourceMarkersPane', interactive: false});

        // Add polygon for fitbounds
        //feature.bringToFront();
        feature.addTo( this.resourceMarkersGroup );
        this.isPolygonSpatial = true;

      }

    });

    // No locations found means no map needed, return.
    if(!this.resourceMarkersGroup?.getLayers().length) {
      // Set map null to hide!
      this.map = null;
      return;
    }

    // Get current resources nearby locations and create markers
    if(this.resource.nearby) {
      let nearbys: any[] = [];
      this.resource.nearby?.forEach((nearbyResource: any) => {
        nearbyResource.spatial?.forEach((spatial: any) => {
          let marker = this.getMarker(nearbyResource.id, spatial, null, nearbyResource.title.text);
          if(marker) {
            marker.addTo(this.nearbyMarkersGroup);
          }
        });
      });
    }

    // Set nearby layers Z-index way back
    this.bringLayersToBack(this.nearbyMarkersGroup);
    this.nearbyMarkersGroup.addTo(this.map);
    this.resourceMarkersGroup.addTo(this.map);
    
    // fitBounds for polygons needs delay!!
    await utils.delay(50);
    let allLayers = this.resourceMarkersGroup.getLayers().concat(this.nearbyMarkersGroup.getLayers());
    const group = L.featureGroup(allLayers);
    this.map.fitBounds(group.getBounds(),this.mapZoomPadding);

    this.$nextTick(() => {
     this.map.invalidateSize();
    });

  }

  /**
   * Create markers to render on map
   */
  getMarker(resourceId: any, spatial: any, markerType: any, markerLabel: string): L.Marker {
    // console.log( resourceId, spatial, markerType, markerLabel);
    let latLng: L.LatLng = null;

    if(spatial.geopoint) {
      // spatial is geopoint
      latLng = L.latLng(spatial.geopoint.lat, spatial.geopoint.lon );
      markerType == null ? markerType = mapUtils.markerType.point.marker : markerType = markerType;
    } else {
      // spatial is geoshape
      let geoShape = spatial?.polygon || spatial?.boundingbox;
      if(geoShape) {
        // get center of geoshape
        const wkt = new Wkt.Wkt();
        wkt.read(geoShape);
        const feature = wkt.toObject({ color: 'red' });
        latLng = L.latLng(feature.getBounds().getCenter().lat, feature.getBounds().getCenter().lng );
        markerType == null ? markerType = mapUtils.markerType.point.shape : markerType = markerType;
      }
    }

    if(latLng) {
      let marker = L.marker(latLng, this.getMarkerStyle(markerType));
      if(markerLabel) {
        //marker.bindTooltip(latLng.toString());
        marker.bindTooltip(markerLabel);
      }
      // Link all makrkers that are not current resource
      if(markerType != mapUtils.markerType.point.current) {
        marker.on('click', () => {
          resourceModule.navigateToResource(resourceId);
        });
      }
      return marker;
    }

    return null;

  }

  /**
   * Bring all layers in group to back
   */
  bringLayersToBack( layerGroup: any ) {
    layerGroup.eachLayer( (layer: any) => {
      layer.setZIndexOffset(-2000);
    });
  }

  /**
   * Resource marker style
   */
  getMarkerStyle(markerType: any) {
    return {
      icon: L.icon({
        iconUrl: markerType,
        iconSize: [25, 41],
        iconAnchor: [12, 40],
        shadowUrl: mapUtils.markerType.shadow.marker,
        shadowSize: [41, 41],
        shadowAnchor: [12, 40]
      }),
      riseOnHover: true
    }
  }
}


</script>
