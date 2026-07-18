from html.parser import HTMLParser
class P(HTMLParser):
    def __init__(self): super().__init__(); self.path = []
    def handle_starttag(self, tag, attrs):
        if tag in ['img', 'input', 'meta', 'link', 'br', 'hr']: return
        d = dict(attrs); id = d.get('id', ''); self.path.append(id or tag)
        if id == 'main-bottom-nav': print('Path:', ' > '.join(self.path))
    def handle_endtag(self, tag):
        if tag in ['img', 'input', 'meta', 'link', 'br', 'hr']: return
        if self.path: self.path.pop()
parser = P()
parser.feed(open('index.html', encoding='utf-8').read())
