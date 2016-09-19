class Entity {

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
            throw new Error('Entity of type '+this.__getProperty('0')+'has ODD number of elements');
        }

        for (let i = 0; i < entity_lines.length; i += 2) {
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
        let properties = [];
        //TODO check if beahes as exoected (foreach)
        for (let property of this.__properties) {
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


    get type() {
        return this.__getProperty('0');
    }

    get id() {
        return this.__getProperty('5');
    }


    get layer() {
        return this.__getProperty('8');
    }

    get geoString() {
        return null;
    }

}


export default Entity;