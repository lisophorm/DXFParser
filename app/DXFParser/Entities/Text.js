import Entity from './Entity';

class Text extends Entity {
    constructor(entity) { //class constructor
        super(entity);
    }

    //protected function
    __getValue() {
        return this.__getProperty('1');
    }

    //protected function
    __getFontSize() {
        return this.__getProperty('40').toFixed(2);
    }entity

    //protected function
    __getStyle() {
        var style = this.__getProperty('7');

        if (!style) {
            style = 'STANDARD';
        }

        return style;
    }

    //protected function
    __getRotation() {
        var rotation = this.__getProperty('50');

        if (!rotation) {
            rotation = 0;
        }

        return rotation;
    }

    //protected function
    __getCoords() {
        return [
            this.__getProperty('10').toFixed(2), 2, this.__getProperty('20').toFixed(2)
        ];
    }

    //protected function
    __getGeoString() {
        return 'POINT('+
            this.coords[0]+
        ' '+
            this.coords[1]+
        ')';
    }
}

export default Text;