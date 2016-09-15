import alfonso from'./alfonso';

class culo extends alfonso {
    constructor(ciao, mamma) {
        var alfo=super(ciao,mamma);
        return new Proxy(alfo ,this.handler());
        //super(ciao,mamma);
        console.log('costrutto culo');
    }
    handler() {
        return {
            get(target, key, receiver) {
                console.log(`accessed property: ${key}`);
                return Reflect.get(target, key, receiver);
            }
        };
    }
    sputa(){
        console.log('vaangulo a',this.zebra['otto']);
    }
}
export default culo;