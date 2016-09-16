import alfonso from "./alfonso";
import culo from'./culo';
import DXFParser from './DXFParser/DxfParser';

var text = 'ciao';

var cacca = new culo(3, 3);
cacca.a = 3;

//var entitiesRegExp = /0\n*SECTION\n.2\nENTITIES[^]*0\n*ENDSEC/;

//console.log('reg',entitiesRegExp);

fetch('./static/Siteplan2018.4.dxf')
    .then(function (reso) {
        return reso.text();
    })
    .then(function (risultato) {
        churnDFX(risultato);
    })
    .catch(function (erro) {
        console.log('errore');
        throw erro;
    });


var churnDFX = function (risultato) {
    console.log('mannagia al cionco');
    let layerTypes=['object', 'plot', 'plot_ref'];
    var sep = /\n/;
    var cionki = risultato.split(sep);
    var parsedDF = new DXFParser(risultato);
    var DXFEntities = parsedDF.getEntities();

    var sanity_check_errors = [];
    var entities_simple = [];
    for (var entity_dxf of DXFEntities) {
        var entity = parsedDF.getEntityObject(entity_dxf);
        if (entity.type === 'LWPOLYLINE') {
            console.log(entity.coords);
            debugger;
        }
        if (!entity) {
            console.log('NO entity');
            continue;
        }
        if (entity.type !== 'TEXT' && entity.type !== 'LWPOLYLINE') {
            continue;
        }
        //ignore unsupported layers
        for(var layer_type of layerTypes) {
            let gino=entity.layer;
            entity.cacca=8;
            console.log(entity.coords);
            let enzo=entity.cacca;
            if(entity.layer.indexOf('#'+layer_type)===0) {
                entity.layer_type=layer_type;
                continue;
            }

            debugger;
        }


    }

    console.log('finito');
    console.log(DXFEntities.length);
};

