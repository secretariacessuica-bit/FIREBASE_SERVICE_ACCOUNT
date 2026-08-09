import re

file_path = r'c:\Users\Wande\Documents\ia\6_APP_LAUSANNE_TESOURARIA\tesouraria_cme_app\lib\presentation\pages\wizard_page.dart'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix const constructors - let's just make the replacements
content = content.replace('Padding(padding: EdgeInsets.all(24.0)', 'const Padding(padding: EdgeInsets.all(24.0)')
content = content.replace('Center(child: Text("Nenhum lançamento identificado."', 'const Center(child: Text("Nenhum lançamento identificado."')
content = content.replace('Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12)', 'const Padding(padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12)')
content = content.replace('Padding(padding: EdgeInsets.symmetric(vertical: 12)', 'const Padding(padding: EdgeInsets.symmetric(vertical: 12)')
content = content.replace('Padding(padding: EdgeInsets.only(top: 8.0, bottom: 16.0)', 'const Padding(padding: EdgeInsets.only(top: 8.0, bottom: 16.0)')
content = content.replace('Padding(padding: EdgeInsets.only(top: 8.0)', 'const Padding(padding: EdgeInsets.only(top: 8.0)')
content = content.replace('Padding(padding: EdgeInsets.only(top: 16.0)', 'const Padding(padding: EdgeInsets.only(top: 16.0)')
content = content.replace('Padding(padding: EdgeInsets.only(bottom: 12.0)', 'const Padding(padding: EdgeInsets.only(bottom: 12.0)')

# Fix deprecated value -> initialValue
content = content.replace('DropdownButtonFormField<String>(\n          value:', 'DropdownButtonFormField<String>(\n          initialValue:')
content = content.replace('DropdownButtonFormField<String>(\n          initialValue: _coTreasurerController', 'DropdownButtonFormField<String>(\n          value: _coTreasurerController')
content = content.replace('value: _coTreasurerController', 'value: _coTreasurerController') # it's deprecated but it's ok for now? wait, no. Flutter 3.33 deprecated value in DropdownButtonFormField? Actually, it did! 
# I will use 'value' as 'value', wait. I will just replace `value:` with `value:` to keep it if it's not deprecated in `DropdownButton`. 
# The issue said: "value is deprecated... Use initialValue instead". 
# So:
content = content.replace('value: _coTreasurerController.text.isEmpty', 'initialValue: _coTreasurerController.text.isEmpty')

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')
