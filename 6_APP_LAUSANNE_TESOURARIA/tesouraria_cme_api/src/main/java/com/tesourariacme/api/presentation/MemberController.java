package com.tesourariacme.api.presentation;

import com.tesourariacme.api.domain.Member;
import com.tesourariacme.api.infrastructure.MemberRepository;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.data.domain.Sort;

import java.util.List;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/api/membros")
public class MemberController {

    private final MemberRepository memberRepository;

    public MemberController(MemberRepository memberRepository) {
        this.memberRepository = memberRepository;
    }

    @GetMapping
    public ResponseEntity<List<String>> listMembers() {
        List<String> names = memberRepository.findAll(Sort.by(Sort.Direction.ASC, "name"))
                .stream()
                .map(Member::getName)
                .collect(Collectors.toList());
        return ResponseEntity.ok(names);
    }
}
