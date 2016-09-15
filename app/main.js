import alfonso from "./alfonso";
import culo from'./culo';
import DXFParser from './DXFParser/DxfParser';

var text = 'ciao';

var cacca = new alfonso(3, 3);
cacca.richiamino();

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
    var sep = /\n/;
    var cionki = risultato.split(sep);
    var parsedDF = new DXFParser(risultato);
    var DXFEntities=parsedDF.getEntities();

    var sanity_check_errors = [];
    var entities_simple = [];
    for (var entity_dxf of DXFEntities) {
        var entity = parsedDF.getEntityObject(entity_dxf);
        if(!entity) {
            console.log('NO entity');
        }
        console.log('is entity');
    }

    console.log('finito');
    console.log(DXFEntities.length);
};

