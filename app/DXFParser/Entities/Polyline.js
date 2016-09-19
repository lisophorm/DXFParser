import Entity from './Entity';

class Polyline extends Entity {
    constructor(entity) { //class constructor
        super(entity);
    }

    // protected function
    get vertices() {
        return this.__getProperty('90');
    }

    //protected function
    get isClosed() {
        return this.__getProperty('70');
    }

    //protected function
    get coords() {
        let x_coords = this.__getProperty('10', true);
        let y_coords = this.__getProperty('20', true);
        let coords = [];
        for (let key in x_coords) {
            coords.push(
                [
                    new Number(x_coords[key]).toFixed(4), new Number(y_coords[key]).toFixed(4)
                ]
            );
        }

        //force unclosing of polygons
        let first_coord = coords[0];
        let last_coord = coords[key];
        // TODO check if this code behaves as expected
        if (last_coord[0] == first_coord[0] && last_coord[1] == first_coord[1]) {
            coords.pop();
        }

        // TODO this was commented in the original PHP file
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
    get geoString() {
        //get coords string
        let coords = '';
        //TODO format properly the return
        for (let coord_pair of this.coords) {
            coords += coord_pair[0] + ' ' + coord_pair[1] + ',';
        }

        return 'POLYGON((' + coords + '))';
    }

}

export default Polyline;