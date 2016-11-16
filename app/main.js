import DXFParser from './DXFParser/DxfParser';


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
            entity.foo=8;
            console.log(entity.coords);
            let enzo=entity.foo;
            if(entity.layer.indexOf('#'+layer_type)===0) {
                entity.layer_type=layer_type;
                continue;
            }
        }


    }

    console.log('finito');
    console.log(DXFEntities.length);
};

