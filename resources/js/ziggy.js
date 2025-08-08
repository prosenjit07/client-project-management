const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"home":{"uri":"\/","methods":["GET","HEAD"]},"project.register.create":{"uri":"projects\/register","methods":["GET","HEAD"]},"project.register.finalize":{"uri":"projects\/register\/finalize","methods":["POST"]},"admin.projects.index":{"uri":"admin\/projects","methods":["GET","HEAD"]},"admin.projects.export.excel":{"uri":"admin\/projects\/export\/excel","methods":["GET","HEAD"]},"admin.projects.export.pdf":{"uri":"admin\/projects\/export\/pdf","methods":["GET","HEAD"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
