
/**
 * Common map functions shared for map (leaflet)
 */
 import { generalModule } from "@/store/modules";

 import * as L from "leaflet";

 // Leaflet stuff
 import "leaflet.heat";
 import "leaflet.markercluster";
 import "leaflet/dist/leaflet.css";

 import "leaflet.markercluster/dist/MarkerCluster.css";
 import "leaflet.markercluster/dist/MarkerCluster.Default.css";

import 'wicket/wicket-leaflet';
import Wkt from 'wicket';


 export default {

  markerType: {
    point: {
      marker: generalModule.getAssetsDir+'/leaflet/marker-icon-geopoint.png',
      shape: generalModule.getAssetsDir+'/leaflet/marker-icon-geoshape.png',
      current: generalModule.getAssetsDir+'/leaflet/marker-icon-current.png'
    },
    approx: {
      marker: generalModule.getAssetsDir+'/leaflet/marker-icon-geopoint-approx.png',
      shape: generalModule.getAssetsDir+'/leaflet/marker-icon-geoshape-approx.png'
    },
    shadow: {
      marker: generalModule.getAssetsDir+'/leaflet/marker-shadow.png'
    },
    combo: {
      marker: generalModule.getAssetsDir+'/leaflet/marker-icon-combo.png'
    }

  },

  getLatLngPointsForResource(resource: any): L.LatLng[] {
    let points: L.LatLng[] = [];

    resource.spatial?.forEach((spatial: any, i: number) => {
      // gepoint
      if (spatial?.geopoint) {
        points.push(new L.LatLng(spatial.geopoint.lat, spatial.geopoint.lon));
      }
      // polygon / boundingbox
      else if (spatial?.polygon || spatial?.boundingbox) {
        // incoming data can either be polygon or boundingbox. handles the same way.
        let shape = spatial?.polygon || spatial?.boundingbox;

        // get centered shape bounds from a new Wicket instance
        // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
        shape = new Wkt.Wkt().read(shape).toObject().getBounds().getCenter();

        // add points
        points.push(new L.LatLng(shape.lat, shape.lng));
      }
    });

    return points;
  },

  getMarkerIconType(type: any): L.Icon {
    return L.icon({
      iconUrl: type,
      iconSize: [25, 41],
      iconAnchor: [12, 40],
      shadowUrl: this.markerType.shadow.marker,
      shadowSize: [41, 41],
      shadowAnchor: [12, 40],
    });
  },

   /**
   * Custom gradients for heatmap
   */
    getGradient(opacity: number) {
      let gradient: any = {};
      for (let i = 1; i <= 10; i++) {
        let key = Math.round(((opacity * i) / 10) * 100) / 100,
          val = Math.round((1.0 - i / 10) * 240);
        gradient[key] = `hsl(${val}, 90%, 50%)`;
      }
      return gradient;
    },

}
