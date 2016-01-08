Class ("paella.ShowEditorTtkPlugin",paella.ShowEditorPlugin,{
        action:function(button) {
            paella.events.trigger(paella.events.showEditor);
        },
        getName:function() {
                return "es.teltek.paella.showEditorTtkPlugin";
        }
});

paella.plugins.showEditorTtkPlugin = new paella.ShowEditorTtkPlugin();
