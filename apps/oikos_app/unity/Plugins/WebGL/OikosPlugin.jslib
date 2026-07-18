mergeInto(LibraryManager.library, {
  SendMemberSelected: function (id) {
    var memberId = UTF8ToString(id);
    // Envia o evento via postMessage para o Flutter parent
    window.parent.postMessage({
      type: 'UNITY_MEMBER_SELECTED',
      memberId: memberId
    }, '*');
  }
});
