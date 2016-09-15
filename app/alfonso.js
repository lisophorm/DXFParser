class alfonso {
    constructor(ciao, mamma) {
        console.log('ciao');
        console.log('die');
        this.ciao = ciao;
        this.mamma = mamma;
        this.zebra = [];
        this.zebra['otto'] = 'tre';
        this.varra = "";
        this.ciccio = function () {
            console.log('dentro al ciccio');
            return 3;
        }
        console.log(this.zebra['otto']);
    }

    richiamino() {
        console.log('il richiamini', this.questo());
    }

    metodo() {
        console.log('mi chiavo il metodo');
        var funca = 'ciccio';
        if (typeof this[funca] === 'function') {
            console.log('ciccio e\' una funzia');
            var retto = this[funca].apply();
        }
        console.log('dentro al retto', retto);

    }

    questo() {
        return this.zebra['otto'];
    }

    get attro() {
        return this.varra;
    }

    set attro(value) {
        this.varra = value;
    }
}


export default alfonso;

