import Entity from './Entity';

class Text extends Entity {
    constructor(entity) { //class constructor
        super(entity);
    }

    //protected function
    get value() {
        return this.__getProperty('1');
    }

    //protected function
    get fontSize() {
        return new Number(this.__getProperty('40')).toFixed(2);
    }

    //protected function
    get style() {
        var style = this.__getProperty('7');

        if (!style) {
            style = 'STANDARD';
        }

        return style;
    }

    //protected function
    get rotation() {
        var rotation = this.__getProperty('50');

        if (!rotation) {
            rotation = 0;
        }

        return rotation;
    }

    //protected function
    get coords() {
        return [
            new Number(this.__getProperty('10')).toFixed(2), new Number(this.__getProperty('20')).toFixed(2)
        ];
    }

    //TODO discuss if x,y or array
    get geoString() {
        return 'POINT('+
            this.coords[0]+
        ' '+
            this.coords[1]+
        ')';
    }
}

export default Text;