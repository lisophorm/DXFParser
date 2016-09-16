import Entity from './Entity';

class Polyline extends Entity {
    constructor(entity) { //class constructor
        super(entity);
    }

    // protected function
    __getVertices() {
        return this.__getProperty('90');
    }

    //protected function
    __getIsClosed() {
        return this.__getProperty('70');
    }

    //protected function
    get coords() {
        var x_coords = this.__getProperty('10', true);
        var y_coords = this.__getProperty('20', true);
        var coords = [];
        for (var key in x_coords) {
            coords.push(
                [
                    // TODO debug this one
                    new Number(x_coords[key]).toFixed(4), new Number(y_coords[key]).toFixed(4)
                ]
            );
        }

        //force unclosing of polygons
        var first_coord = coords[0];
        var last_coord = coords[key];
        // TODO also check this one
        if (last_coord[0] == first_coord[0] && last_coord[1] == first_coord[1]) {
            coords.pop();
        }

        /*
         //coords might not be closed - close them
         first_coord = coords[0];
         last_coord = coords[key];
         if(last_coord[0] != first_coord[0] && last_coord[1] != first_coord[1])
         {
         coords[] = first_coord;
         }
    //
         */

        return coords;
    }

    //protected function
    __getGeoString() {
        //get coords string
        coords = '';
        //TODO check if beahes as exoected (foreach)
        for (var coord_pair of this.coords) {
            coords += coord_pair[0] + ' ' + coord_pair[1] + ',';
        }

        return 'POLYGON((' + coords + '))';
    }

}

export default Polyline;