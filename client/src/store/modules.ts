import store from './';
import { Aggregation } from './modules/Aggregation';
import { General } from './modules/General';
import { Resource } from './modules/Resource';
import { Search } from './modules/Search';
import { Subject } from './modules/Subject';
import { Contact } from './modules/Contact';

export const general = new General({ store, name: "general" });
export const resource = new Resource(general, { store, name: "resource" });
export const search = new Search(general, { store, name: "search" });
export const aggregation = new Aggregation(search, { store, name: "aggregation" });
export const subject = new Subject(general, { store, name: "subject" });
export const contact = new Contact(general, { store, name: "contact" });
