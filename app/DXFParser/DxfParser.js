import Entity from './Entities/Entity';
import Polyline from './Entities/Polyline';
import Text from './Entities/Text';


class DxfParser {

    //public function
    constructor(dxf) {
        console.log('costrisci dxf');
        this.__dxf = dxf.replace(/\r\n/g, "\n");
        this.__entities = null;
        // static yeah
        this.__entityTypesString = '3DFACE|3DSOLID|ACAD_PROXY_ENTITY|ARC|ATTDEF|ATTRIB|BODY|CIRCLE|DIMENSION|ELLIPSE|HATCH|HELIX|IMAGE|INSERT|LEADER|LIGHT|LINE|LWPOLYLINE|MLINE|MLEADER|MLEADERSTYLE|MTEXT|OLEFRAME|OLE2FRAME|POINT|POLYLINE|RAY|REGION|SECTION|SEQEND|SHAPE|SOLID|SPLINE|SUN|SURFACE|TABLE|TEXT|TOLERANCE|TRACE|UNDERLAY|VERTEX|VIEWPORT|WIPEOUT|XLINE';
    }

    //public function
    getEntities() {
        if (this.__entities) {
            return this.__entities;
        }

        //get entities section
        var entitiesRegExp = /0\nSECTION\n(.*)2\nENTITIES[\s\S]*0\nENDSEC/;
        // var entitiesRegExp = 0\r\nSECTION[/S/s]*

        var matches = entitiesRegExp.exec(this.__dxf);

        var entities_section = matches[0];


        var entities_section_with_breaks = entities_section.replace(/\s?0\n(3DFACE|3DSOLID|ACAD_PROXY_ENTITY|ARC|ATTDEF|ATTRIB|BODY|CIRCLE|DIMENSION|ELLIPSE|HATCH|HELIX|IMAGE|INSERT|LEADER|LIGHT|LINE|LWPOLYLINE|MLINE|MLEADER|MLEADERSTYLE|MTEXT|OLEFRAME|OLE2FRAME|POINT|POLYLINE|RAY|REGION|SECTION|SEQEND|SHAPE|SOLID|SPLINE|SUN|SURFACE|TABLE|TEXT|TOLERANCE|TRACE|UNDERLAY|VERTEX|VIEWPORT|WIPEOUT|XLINE)/g, (match) => {
            var current = '[[ENTITY_SEPARATOR]]' + match.trim();
            return current;
        });


        var entities = entities_section_with_breaks.split('[[ENTITY_SEPARATOR]]');

        entities.shift();
        entities.shift();
        return entities;

    }


    getType(entity) {

        var singleEntityReg = 0;
        var entRegEx = /0\n*(3DFACE|3DSOLID|ACAD_PROXY_ENTITY|ARC|ATTDEF|ATTRIB|BODY|CIRCLE|DIMENSION|ELLIPSE|HATCH|HELIX|IMAGE|INSERT|LEADER|LIGHT|LINE|LWPOLYLINE|MLINE|MLEADER|MLEADERSTYLE|MTEXT|OLEFRAME|OLE2FRAME|POINT|POLYLINE|RAY|REGION|SECTION|SEQEND|SHAPE|SOLID|SPLINE|SUN|SURFACE|TABLE|TEXT|TOLERANCE|TRACE|UNDERLAY|VERTEX|VIEWPORT|WIPEOUT|XLINE)/;
        var matches = entRegEx.exec(entity);
        var entities_section = matches[1];
        if (matches[1]) {
            return matches[1];
        } else {
            return null;
        }
    }

//     //public static function
    getEntityObject(entity) {
        var type = this.getType(entity);
        let handler = {
            get (target, key) {
                console.log('handler get');
                console.log('key:',key);
                if (typeof(target.__cache[key]) !== "undefined" && target.__cache[key] !== null) {
                    console.log('CACHE EXISTS',key);
                    return target.__cache[key];
                }
                if (typeof(target[key]) !== "undefined" && target[key] !== null) {
                    console.log('CACHE NOT EXISTS',key);
                    target.__cache[key]=target[key];
                    return target.__cache[key];
                } else {
                    return null;
                }
            },
            set (target, key, value) {
                console.log('handler set');
                target.__cache[key]=value;
                debugger;
                return true
            }
        }
        //debugger;
        if (type === 'LWPOLYLINE') {
            var result = new Proxy(new Polyline(entity), handler);
            //debugger;
            return result;
        } else if (type === 'TEXT') {
            var result = new Proxy(new Text(entity), handler);
            //debugger;
            return result;
        }
        return null;
    }

    //public static function
    //


}

export default DxfParser;