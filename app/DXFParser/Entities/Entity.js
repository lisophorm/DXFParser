class Entity {

    //protected $__entity = null;
    //	protected $__properties = [];
    //protected $__cache = [];
    //
    constructor(entity) {
        this.__entity = entity;
        this.__properties = [];
        this.__cache = [];

        //get entity properties
        let entity_lines = entity.trim();
        entity_lines = entity_lines.split(/\n/);
        if(entity_lines[1]!=='TEXT') {
            //debugger;
        }
        if(entity_lines.length%2 !=0) {
            debugger;
        }

        for (var i = 0; i < entity_lines.length; i += 2) {
            // using push instead of []
            this.__properties.push(
                [entity_lines[i].trim(),
                    entity_lines[i + 1].trim()]
            )
        }

    }

    //get property - done like this as properties can be repeated (like in coord points)
    //private
    __getProperty(ref, multiple = false) {
        var properties = [];
        //TODO check if beahes as exoected (foreach)
        for (var property of this.__properties) {
            if (property[0] === ref) {
                // using push instead of []
                properties.push(property[1]);
            }
        }
        if (multiple) {
            return properties;
        }

        if (typeof properties[0] !== 'undefined') {
            return properties[0];
        }

        // veryfy if javascript null satisfies the condition

        return null;
    }

    //priv
    get type() {
        return this.__getProperty('0');

    }

    get id() {
        return this.__getProperty('5');
    }

    //priv
    get layer() {
        return this.__getProperty('8');
    }

    //priv
    get geoString() {
        return null;
    }

    //public
    __get(property) {
        //get from cache
        if (typeof(this.__cache[property]) !== "undefined" && this.__cache[property] !== null) {
            return this.__cache[property];
        }


        //get data
        var value = null;
        // first char of property uppercase
        var method_name = '__get' + '' + property.charAt(0).toUpperCase() + property.substr(1);

        if (typeof this[method_name] === 'function') {
            console.log('executing method', method_name);
            value = this[method_name].apply();
        }

        /*
         ******** VERIFY THIS LITTLE BUGGER
         var method_name = '__get'.ucfirst($property);
         if (method_exists($this, $method_name)) {
         $value = $this-> {
         $method_name
         }
         ();
         }
         */
        //set to cache
        this.__cache[property] = value;

        return value;
    }

    //public
    __set(property, value) {
        this.__cache[property] = value;
    }
}

// we keep this here just in case

function method_exists(obj, method) {
    // http://jsphp.co/jsphp/fn/view/method_exists
    // + original by: Brett Zamir (http://brett-zamir.me)
    // * example 1: function class_a() {this.meth1 = function () {return true;}};
    // * example 1: var instance_a = new class_a();
    // * example 1: method_exists(instance_a, 'meth1');
    // * returns 1: true
    // * example 2: function class_a() {this.meth1 = function () {return true;}};
    // * example 2: var instance_a = new class_a();
    // * example 2: method_exists(instance_a, 'meth2');
    // * returns 2: false
    if (typeof obj === 'string') {
        return this.window[obj] && typeof this.window[obj][method] === 'function';
    }
    return typeof obj[method] === 'function';
}

export default Entity;